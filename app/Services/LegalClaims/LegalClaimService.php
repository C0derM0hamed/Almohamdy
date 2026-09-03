<?php

namespace App\Services\LegalClaims;

use App\Support\ProtectedFileDownload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegalClaimService
{
    public function canCreate(): bool
    {
        return (int) session('hr_user_level', 0) === 3 || $this->branchId() === 2;
    }

    public function authorizeScope(): void
    {
        abort_unless($this->branchId() > 0 && $this->companyId() > 0, 403);
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $this->authorizeScope();
        $query = $this->filteredQuery($filters)
            ->leftJoin('lawsuit_status as status_lookup', 'status_lookup.id', '=', 'lawsuit.status')
            ->leftJoin('lawsuit_payment_type as payment', 'payment.id', '=', 'lawsuit.lawsuit_payment_type_id')
            ->select('lawsuit.*', 'status_lookup.name_ar as status_name_ar', 'payment.name_ar as payment_name_ar');

        if (Schema::hasColumn('lawsuit_status', 'name_en')) {
            $query->addSelect('status_lookup.name_en as status_name_en');
        }

        if (Schema::hasColumn('lawsuit_payment_type', 'name_en')) {
            $query->addSelect('payment.name_en as payment_name_en');
        }

        if (Schema::hasTable('companies_groups')) {
            $query->leftJoin('companies_groups as company', 'company.id', '=', 'lawsuit.companies_groups_id')
                ->addSelect('company.name_ar as company_name_ar');
            if (Schema::hasColumn('companies_groups', 'name_en')) {
                $query->addSelect('company.name_en as company_name_en');
            }
        }

        if (Schema::hasTable('lawsuit_approval_status')) {
            $query->leftJoin('lawsuit_approval_status as approval', 'approval.id', '=', 'lawsuit.lawsuit_approval_status_id')
                ->addSelect('approval.name_ar as approval_status_name_ar');
            if (Schema::hasColumn('lawsuit_approval_status', 'name_en')) {
                $query->addSelect('approval.name_en as approval_status_name_en');
            }
        }

        return $query
            ->orderByDesc('lawsuit.id')
            ->paginate(15)
            ->withQueryString();
    }

    /** @return Collection<int, object> */
    public function statusDashboard(array $filters): Collection
    {
        $this->authorizeScope();

        return DB::table('lawsuit_status')
            ->where('publish', 1)
            ->orderBy('ranking')
            ->get()
            ->map(function (object $status) use ($filters): object {
                $status->count = (clone $this->filteredQuery($filters))
                    ->where('lawsuit.status', $status->id)
                    ->count();

                return $status;
            });
    }

    public function lookups(): array
    {
        $this->authorizeScope();
        return [
            'statuses' => DB::table('lawsuit_status')->where('publish', 1)->orderBy('ranking')->get(),
            'paymentTypes' => DB::table('lawsuit_payment_type')->where('publish', 1)->orderBy('id')->get(),
            'locations' => DB::table('lawsuit_admission_location')->where('publish', 1)->orderBy('id')->get(),
            'requestStatuses' => DB::table('lawsuit_request_status')->where('publish', 1)->orderBy('id')->get(),
            'rejectedReasons' => DB::table('lawsuit_rejected_reason')->where('publish', 1)->orderBy('id')->get(),
            'suspendStatuses' => DB::table('lawsuit_suspend_case_request_status')->where('publish', 1)->orderBy('ranking')->get(),
            'nationalities' => Schema::hasTable('country_yakeen')
                ? DB::table('country_yakeen')->where('publish', 1)->orderBy('DESCRIPTION')->get(['CODE', 'DESCRIPTION'])
                : collect(),
        ];
    }

    /** بيانات ضمان الدفع التي يعرضها lawsuit_forms.php عند إدخال رقم الملف. */
    public function paymentGuarantee(string $fileNumber): ?object
    {
        $this->authorizeScope();
        if (!Schema::hasTable('payment_guarantee') || trim($fileNumber) === '') {
            return null;
        }

        $query = DB::table('payment_guarantee')->where('patient_file_number', trim($fileNumber));
        if ((int) session('hr_user_level', 0) !== 3 && Schema::hasColumn('payment_guarantee', 'companies_groups_id')) {
            $query->where('companies_groups_id', $this->companyId());
        }

        return $query->orderByDesc('id')->first([
            'patient_name_ar', 'patient_idno', 'patient_file_number', 'patient_nationality',
            'contractor_name_ar', 'contractor_idno', 'contractor_mobile', 'contractor_nationality',
            'date_type', 'birth_day', 'birth_month', 'birth_year', 'sexCode', 'contractor_approval_date',
        ]);
    }

    public function create(array $data, array $files): int
    {
        $this->authorizeScope();
        $lookup = $this->lookups();
        abort_unless($lookup['paymentTypes']->contains('id', (int) $data['lawsuit_payment_type_id']), 422);
        $stored = [];
        foreach (['file_1', 'file_2', 'file_3', 'file_4'] as $key) {
            $stored[$key] = isset($files[$key]) && $files[$key] instanceof UploadedFile ? $files[$key]->store('lawsuit', 'public') : '';
        }

        return (int) DB::table('lawsuit')->insertGetId(array_merge([
            'date' => time(), 'branch_id' => $this->branchId(), 'companies_groups_id' => $this->companyId(),
            'created_by' => (int) session('hr_user_id', 0), 'status' => (int) ($data['status'] ?? 1),
        ], $stored, $this->claimFields($data)));
    }

    public function find(int $id): ?object
    {
        $this->authorizeScope();
        $claim = $this->scopedQuery()->leftJoin('lawsuit_status as status_lookup', 'status_lookup.id', '=', 'lawsuit.status')
            ->leftJoin('lawsuit_payment_type as payment', 'payment.id', '=', 'lawsuit.lawsuit_payment_type_id')
            ->where('lawsuit.id', $id)->select('lawsuit.*', 'status_lookup.name_ar as status_name_ar', 'payment.name_ar as payment_name_ar')->first();
        if ($claim) {
            $claim->actions = DB::table('lawsuit_actions')->leftJoin('lawsuit_status as s', 's.id', '=', 'lawsuit_actions.status_id')->where('lawsuit_id', $id)->orderByDesc('lawsuit_actions.id')->select('lawsuit_actions.*', 's.name_ar as status_name_ar')->get();
            $claim->attachments = DB::table('lawsuit_attachments')->where('lawsuit_id', $id)->orderByDesc('id')->get();
            $claim->statements = DB::table('lawsuit_statement_request')->where('lawsuit_id', $id)->orderByDesc('id')->get();
            $claim->installments = DB::table('lawsuit_reconciliation_installment')->where('lawsuit_id', $id)->orderBy('installment_date')->get();
            $claim->suspensions = DB::table('lawsuit_suspend_case_request as r')->leftJoin('lawsuit_suspend_case_request_status as s', 's.id', '=', 'r.lawsuit_suspend_case_request_status_id')->where('r.lawsuit_id', $id)->orderByDesc('r.id')->select('r.*', 's.name_ar as status_name_ar')->get();
        }
        return $claim;
    }

    public function addAction(int $id, array $data, array $files): void
    {
        $claim = $this->find($id);
        abort_if($claim === null, 404);
        $status = (int) $data['status_id'];
        abort_unless(DB::table('lawsuit_status')->where('id', $status)->where('publish', 1)->exists(), 422);
        DB::transaction(function () use ($id, $data, $files, $status): void {
            $action = [
                'lawsuit_id' => $id, 'status_id' => $status, 'branch_id' => $this->branchId(), 'details' => trim((string) ($data['details'] ?? '')),
                'created_by' => (int) session('hr_user_id', 0), 'request_number' => trim((string) ($data['request_number'] ?? '')),
                'applicant' => trim((string) ($data['applicant'] ?? '')), 'request_date' => $data['request_date'] ?? null, 'case_number' => trim((string) ($data['case_number'] ?? '')),
                'sessions_number' => trim((string) ($data['sessions_number'] ?? '')), 'session_summary' => trim((string) ($data['session_summary'] ?? '')),
                'sessions_date' => $data['sessions_date'] ?? null, 'next_sessions_date' => $data['next_sessions_date'] ?? null,
                'lawsuit_request_file' => $this->storeFile($files['lawsuit_request_file'] ?? null), 'session_1_file' => $this->storeFile($files['session_1_file'] ?? null),
                'judgment_instrument' => $this->storeFile($files['judgment_instrument'] ?? null),
            ];
            DB::table('lawsuit_actions')->insert(array_filter($action, fn (mixed $value, string $column): bool => Schema::hasColumn('lawsuit_actions', $column), ARRAY_FILTER_USE_BOTH));
            DB::table('lawsuit')->where('id', $id)->update(['status' => $status, 'updated_by' => (int) session('hr_user_id', 0), 'updated_at' => now()]);
        });
    }

    public function addAttachment(int $id, UploadedFile $file): void
    {
        abort_if($this->find($id) === null, 404);
        DB::table('lawsuit_attachments')->insert(['lawsuit_id' => $id, 'file_name' => $file->store('lawsuit', 'public'), 'created_by' => (int) session('hr_user_id', 0), 'created_at' => now()]);
    }

    public function addStatement(int $id, string $details, ?UploadedFile $file): void
    {
        abort_if($this->find($id) === null, 404);
        DB::table('lawsuit_statement_request')->insert(['lawsuit_id' => $id, 'branch_id' => $this->branchId(), 'details' => trim($details), 'created_by' => (int) session('hr_user_id', 0), 'created_at' => now(), 'file' => $this->storeFile($file)]);
    }

    public function addInstallment(int $id, string $date): void
    {
        abort_if($this->find($id) === null, 404);
        DB::table('lawsuit_reconciliation_installment')->insert(['lawsuit_id' => $id, 'branch_id' => $this->branchId(), 'companies_groups_id' => $this->companyId(), 'installment_date' => $date, 'created_by' => (int) session('hr_user_id', 0)]);
    }

    public function markInstallmentPaid(int $id, int $installment): void
    {
        abort_if($this->find($id) === null, 404);
        abort_unless(DB::table('lawsuit_reconciliation_installment')->where('id', $installment)->where('lawsuit_id', $id)->exists(), 404);
        DB::table('lawsuit_reconciliation_installment')->where('id', $installment)->where('lawsuit_id', $id)->update(['payment_status' => 1, 'payment_date' => time(), 'payment_intered_by' => (int) session('hr_user_id', 0)]);
    }

    public function addSuspension(int $id, array $data, ?UploadedFile $file): void
    {
        abort_if($this->find($id) === null, 404);
        abort_unless(DB::table('lawsuit_suspend_case_request_status')->where('id', (int) $data['status_id'])->where('publish', 1)->exists(), 422);
        DB::table('lawsuit_suspend_case_request')->insert(['lawsuit_id' => $id, 'lawsuit_suspend_case_request_status_id' => (int) $data['status_id'], 'branch_id' => $this->branchId(), 'created_by' => (int) session('hr_user_id', 0), 'total_amount' => $data['total_amount'] ?? null, 'amount_waived' => $data['amount_waived'] ?? null, 'file' => $this->storeFile($file)]);
    }

    public function download(int $id, string $kind, ?int $childId = null): mixed
    {
        $claim = $this->find($id);
        abort_if($claim === null, 404);
        $path = match ($kind) {
            'claim' => $claim->{'file_'.($childId ?: 1)} ?? null,
            'attachment' => DB::table('lawsuit_attachments')->where('lawsuit_id', $id)->where('id', $childId)->value('file_name'),
            'action' => DB::table('lawsuit_actions')->where('lawsuit_id', $id)->where('id', $childId)->value('lawsuit_request_file'),
            'session' => DB::table('lawsuit_actions')->where('lawsuit_id', $id)->where('id', $childId)->value('session_1_file'),
            'statement' => DB::table('lawsuit_statement_request')->where('lawsuit_id', $id)->where('id', $childId)->value('file'),
            'judgment' => DB::table('lawsuit_actions')->where('lawsuit_id', $id)->where('id', $childId)->value('judgment_instrument'),
            'waiver' => DB::table('lawsuit_suspend_case_request')->where('lawsuit_id', $id)->where('id', $childId)->value('file'),
            default => null,
        };
        return app(ProtectedFileDownload::class)->download($path, 'lawsuit-'.$id.'-'.$kind, []);
    }

    public function timeline(int $id): Collection
    {
        $claim = $this->find($id);
        abort_if($claim === null, 404);
        return collect($claim->actions)->map(fn ($action) => (object) ['label' => $action->status_name_ar ?: 'إجراء', 'date' => $action->created_at, 'details' => $action->details])->values();
    }

    private function claimFields(array $data): array
    {
        $fields = ['lawsuit_payment_type_id','patient_name','admission_date','file_number','discharge_date','patient_nationality','patient_idno','amount_paid','amount_rest','liable_name','liable_idno','liable_nationality','liable_mobile','lawsuit_admission_location_id','covered_amount','uncovered_amount','received_date','lawsuit_request_status_id','lawsuit_rejected_reason_id','date_type','birth_day','birth_month','birth_year','rejected_reason','sexCode','contractor_approval_date','service_provided_to_patient'];
        return collect($fields)->mapWithKeys(fn ($field) => [$field => array_key_exists($field, $data) ? trim((string) $data[$field]) : null])->all();
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = $this->scopedQuery();
        $search = trim((string) ($filters['mobile'] ?? ''));

        $query->when(trim((string) ($filters['patient_name'] ?? '')) !== '', fn (Builder $q) => $q->where('lawsuit.patient_name', 'like', '%'.trim((string) $filters['patient_name']).'%'))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $columns = ['liable_idno', 'file_number', 'case_number', 'request_number', 'liable_mobile'];
                $available = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('lawsuit', $column)));
                $q->where(function (Builder $searchQuery) use ($available, $search): void {
                    foreach ($available as $column) {
                        $searchQuery->orWhere('lawsuit.'.$column, 'like', '%'.$search.'%');
                    }
                });
            })
            ->when((int) ($filters['status_id'] ?? 0) > 0, fn (Builder $q) => $q->where('lawsuit.status', (int) $filters['status_id']))
            ->when(trim((string) ($filters['from'] ?? '')) !== '', fn (Builder $q) => $q->where('lawsuit.date', '>=', strtotime($filters['from'].' 00:00:00')))
            ->when(trim((string) ($filters['to'] ?? '')) !== '', fn (Builder $q) => $q->where('lawsuit.date', '<=', strtotime($filters['to'].' 23:59:59')));

        if (($filters['statement_filter'] ?? '') === 'has_statements' && Schema::hasTable('lawsuit_statement_request')) {
            $query->whereExists(fn (Builder $statement) => $statement
                ->selectRaw('1')
                ->from('lawsuit_statement_request')
                ->whereColumn('lawsuit_statement_request.lawsuit_id', 'lawsuit.id'));
        }

        if (($filters['statement_filter'] ?? '') === 'without_statements' && Schema::hasTable('lawsuit_statement_request')) {
            $query->whereNotExists(fn (Builder $statement) => $statement
                ->selectRaw('1')
                ->from('lawsuit_statement_request')
                ->whereColumn('lawsuit_statement_request.lawsuit_id', 'lawsuit.id'));
        }

        return $query;
    }

    private function scopedQuery(): Builder
    {
        $query = DB::table('lawsuit');

        // مستوى 3 هو مدير النظام العام الذي طلبناه: يرى السجلات من كل الفروع والشركات.
        if ((int) session('hr_user_level', 0) === 3) {
            return $query;
        }

        // هذا يطابق lawsuit.php القديم: فرع التحصيل يرى مجموعة شركته،
        // وفرع الشؤون القانونية يرى فقط المطالبات المعتمدة له.
        if ($this->branchId() === 3 && Schema::hasColumn('lawsuit', 'lawsuit_approval_status_id')) {
            return $query->where('lawsuit.lawsuit_approval_status_id', 1);
        }

        return $query->where('lawsuit.companies_groups_id', $this->companyId());
    }

    private function storeFile(?UploadedFile $file): string { return $file instanceof UploadedFile ? $file->store('lawsuit', 'public') : ''; }
    private function branchId(): int { return (int) session('hr_branch_id', 0); }
    private function companyId(): int { return (int) session('companies_groups_id', 0); }
}

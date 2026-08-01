<?php

namespace App\Services\LegalClaims;

use App\Support\ProtectedFileDownload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LegalClaimService
{
    public function authorizeScope(): void
    {
        abort_unless($this->branchId() > 0 && $this->companyId() > 0, 403);
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $this->authorizeScope();
        $query = $this->scopedQuery()
            ->leftJoin('lawsuit_status as status_lookup', 'status_lookup.id', '=', 'lawsuit.status')
            ->leftJoin('lawsuit_payment_type as payment', 'payment.id', '=', 'lawsuit.lawsuit_payment_type_id')
            ->select('lawsuit.*', 'status_lookup.name_ar as status_name_ar', 'payment.name_ar as payment_name_ar')
            ->orderByDesc('lawsuit.id');

        $query->when($filters['patient_name'] !== '', fn ($q) => $q->where('lawsuit.patient_name', 'like', '%'.$filters['patient_name'].'%'))
            ->when($filters['mobile'] !== '', fn ($q) => $q->where('lawsuit.liable_mobile', 'like', '%'.$filters['mobile'].'%'))
            ->when($filters['status_id'] > 0, fn ($q) => $q->where('lawsuit.status', $filters['status_id']))
            ->when($filters['from'] !== '', fn ($q) => $q->whereRaw('FROM_UNIXTIME(lawsuit.date) >= ?', [$filters['from'].' 00:00:00']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereRaw('FROM_UNIXTIME(lawsuit.date) <= ?', [$filters['to'].' 23:59:59']));

        return $query->paginate(15)->withQueryString();
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
        ];
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
            DB::table('lawsuit_actions')->insert([
                'lawsuit_id' => $id, 'status_id' => $status, 'branch_id' => $this->branchId(), 'details' => trim((string) ($data['details'] ?? '')),
                'created_by' => (int) session('hr_user_id', 0), 'request_number' => trim((string) ($data['request_number'] ?? '')),
                'request_date' => $data['request_date'] ?? null, 'case_number' => trim((string) ($data['case_number'] ?? '')),
                'sessions_number' => trim((string) ($data['sessions_number'] ?? '')), 'session_summary' => trim((string) ($data['session_summary'] ?? '')),
                'sessions_date' => $data['sessions_date'] ?? null, 'next_sessions_date' => $data['next_sessions_date'] ?? null,
                'lawsuit_request_file' => $this->storeFile($files['lawsuit_request_file'] ?? null), 'session_1_file' => $this->storeFile($files['session_1_file'] ?? null),
            ]);
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

    private function scopedQuery()
    {
        return DB::table('lawsuit')->where('lawsuit.branch_id', $this->branchId())->where('lawsuit.companies_groups_id', $this->companyId());
    }

    private function storeFile(?UploadedFile $file): string { return $file instanceof UploadedFile ? $file->store('lawsuit', 'public') : ''; }
    private function branchId(): int { return (int) session('hr_branch_id', 0); }
    private function companyId(): int { return (int) session('companies_groups_id', 0); }
}

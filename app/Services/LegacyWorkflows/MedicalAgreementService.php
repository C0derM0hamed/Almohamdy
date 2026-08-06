<?php

namespace App\Services\LegacyWorkflows;

use App\Services\Auth\LegacyScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MedicalAgreementService
{
    public function __construct(private readonly LegacyScopeService $legacyScopes) {}

    public const STANDARD = 'standard';

    public const SADQ = 'sadq';

    public const SADQ_MANUAL = 'sadq-manual';

    /** @return list<string> */
    public function variants(): array
    {
        return [self::STANDARD, self::SADQ, self::SADQ_MANUAL];
    }

    public function authorize(string $variant): void
    {
        abort_unless(in_array($variant, $this->variants(), true), 404);
        abort_unless((int) session('companies_groups_id', 0) > 0, 403);

        if ($variant !== self::STANDARD) {
            abort_unless(in_array((int) session('hr_branch_id', 0), [1, 2, 5, 8], true), 403);
            $permission = $variant === self::SADQ
                ? 'Medical Services Provision Agreement Yaqeen'
                : 'Medical Services Provision Agreement non Yaqeen';

            abort_unless($this->hasLegacyPrivilege($permission) || $this->legacyScopes->allows(LegacyScopeService::SADQ), 403);
        }
    }

    /** @param array<string, string> $filters */
    public function list(string $variant, array $filters): LengthAwarePaginator
    {
        $this->authorize($variant);
        $table = $this->table($variant);
        $query = DB::table($table.' as agreement')
            ->leftJoin('ra_users as creator', 'creator.hr_id', '=', 'agreement.user_id')
            ->where('agreement.branch_id', $this->branchId())
            ->where('agreement.companies_groups_id', $this->companyId())
            ->select('agreement.*', 'creator.hr_first_name as creator_name')
            ->orderByDesc('agreement.id');

        if ($filters['from'] !== '') {
            $query->where('agreement.created_at', '>=', $filters['from'].' 00:00:00');
        }
        if ($filters['to'] !== '') {
            $query->where('agreement.created_at', '<=', $filters['to'].' 23:59:59');
        }
        if ($filters['language'] !== '') {
            $query->where('agreement.language', (int) $filters['language']);
        }
        if ($filters['creator'] !== '') {
            $query->where('agreement.user_id', (int) $filters['creator']);
        }
        if ($filters['id_number'] !== '') {
            $query->where(function ($nested) use ($filters): void {
                $nested->where('agreement.patient_idno', 'like', '%'.$filters['id_number'].'%')
                    ->orWhere('agreement.contractor_idno', 'like', '%'.$filters['id_number'].'%');
            });
        }
        if ($filters['status'] !== '') {
            if ($variant === self::STANDARD) {
                $filters['status'] === 'pending'
                    ? $query->whereNull('agreement.emdha_output')
                    : $query->whereNotNull('agreement.emdha_output');
            } else {
                $query->whereExists(function ($transaction) use ($filters): void {
                    $transaction->selectRaw('1')
                        ->from('medical_services_agreement_sadq_transactions as tx')
                        ->whereColumn('tx.reference_number', 'agreement.reference_number')
                        ->where('tx.signStatus', $filters['status']);
                });
            }
        }

        return $query->paginate(15)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function options(string $variant): array
    {
        $this->authorize($variant);

        return [
            'countries' => DB::table('country_yakeen')->orderBy('DESCRIPTION')->get(),
            'idTypes' => DB::table('idtype')->orderBy('id')->get(),
            'relatives' => DB::table('relatives')->orderBy('id')->get(),
            'creators' => DB::table('ra_users')
                ->where('branch_id', $this->branchId())
                ->where('companies_groups_id', $this->companyId())
                ->orderBy('hr_first_name')->get(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(string $variant, array $data): int
    {
        $this->authorize($variant);
        $table = $this->table($variant);
        $now = now()->format('Y-m-d H:i:s');
        $contractorIsPatient = (int) $data['contractor_type'] === 2;
        $reference = Str::uuid()->toString();

        if ($variant !== self::STANDARD) {
            $duplicateExists = DB::table($table)
                ->where('patient_idno', trim((string) $data['patient_idno']))
                ->where('contractor_mobile', trim((string) $data['contractor_mobile']))
                ->where('created_at', '>=', now()->subHour()->format('Y-m-d H:i:s'))
                ->exists();
            abort_if($duplicateExists, 422, 'لا يمكن إنشاء طلب جديد بنفس رقم الهوية ورقم الجوال إلا بعد مرور ساعة.');
        }

        $record = [
            'branch_id' => $this->branchId(),
            'companies_groups_id' => $this->companyId(),
            'user_id' => (int) session('hr_user_id', 0),
            'patient_name_ar' => trim((string) ($data['patient_name_ar'] ?? '')) ?: null,
            'patient_name_en' => trim((string) ($data['patient_name_en'] ?? '')) ?: null,
            'patient_idno' => trim((string) $data['patient_idno']),
            'patient_file_number' => trim((string) $data['patient_file_number']),
            'patient_nationality' => (int) $data['patient_nationality'],
            'contractor_name_ar' => $contractorIsPatient ? trim((string) ($data['patient_name_ar'] ?? '')) : (trim((string) ($data['contractor_name_ar'] ?? '')) ?: null),
            'contractor_name_en' => $contractorIsPatient ? trim((string) ($data['patient_name_en'] ?? '')) : (trim((string) ($data['contractor_name_en'] ?? '')) ?: null),
            'contractor_idno' => $contractorIsPatient ? trim((string) $data['patient_idno']) : trim((string) $data['contractor_idno']),
            'contractor_mobile' => trim((string) $data['contractor_mobile']),
            'contractor_nationality' => $contractorIsPatient ? (int) $data['patient_nationality'] : (int) $data['contractor_nationality'],
            'created_at' => $now,
            'relative' => $contractorIsPatient ? '0' : (string) ($data['relative'] ?? '0'),
            'language' => (int) $data['language'],
            'contractor_type' => (int) $data['contractor_type'],
            'date_type' => (int) ($data['date_type'] ?? 0),
            'birth_day' => (int) ($data['birth_day'] ?? 0),
            'birth_month' => (int) ($data['birth_month'] ?? 0),
            'birth_year' => (int) ($data['birth_year'] ?? 0),
            'pateintIDType' => (int) ($data['patient_id_type'] ?? 0),
            'contractorIDType' => $contractorIsPatient ? (int) ($data['patient_id_type'] ?? 0) : (int) ($data['contractor_id_type'] ?? 0),
            'sexCode' => (int) ($data['sex_code'] ?? 0),
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
        ];

        if ($variant === self::STANDARD) {
            $record += ['sms_tocken' => Str::random(32), 'status' => 0, 'type' => 1, 'payment_type' => 0, 'deserved_amount' => '0'];
        } else {
            $record += ['reference_number' => $reference, 'status' => 0, 'type' => 1, 'payment_type' => 0, 'deserved_amount' => '0'];
        }

        return (int) DB::table($table)->insertGetId($record);
    }

    public function find(string $variant, int $id): ?object
    {
        $this->authorize($variant);
        $record = DB::table($this->table($variant).' as agreement')
            ->leftJoin('ra_users as creator', 'creator.hr_id', '=', 'agreement.user_id')
            ->where('agreement.id', $id)
            ->where('agreement.branch_id', $this->branchId())
            ->where('agreement.companies_groups_id', $this->companyId())
            ->select('agreement.*', 'creator.hr_first_name as creator_name', 'creator.hr_last_name as creator_last_name')
            ->first();

        if ($record === null) {
            return null;
        }

        $record->attachments = DB::table('payment_guarantee_attachments')
            ->where('payment_guarantee_id', $id)->orderBy('id')->get();
        $record->transactions = $variant === self::STANDARD
            ? collect()
            : DB::table('medical_services_agreement_sadq_transactions')
                ->where('reference_number', $record->reference_number)->orderBy('id')->get();

        return $record;
    }

    public function attach(string $variant, int $id, UploadedFile $file): void
    {
        abort_if($this->find($variant, $id) === null, 404);
        $path = $file->store('medical-agreements', 'public');
        DB::table('payment_guarantee_attachments')->insert([
            'payment_guarantee_id' => $id,
            'file_name' => $path,
            'created_by' => (int) session('hr_user_id', 0),
        ]);
    }

    public function attachment(string $variant, int $id, int $attachmentId): ?object
    {
        abort_if($this->find($variant, $id) === null, 404);

        return DB::table('payment_guarantee_attachments')
            ->where('id', $attachmentId)->where('payment_guarantee_id', $id)->first();
    }

    public function deleteAttachment(string $variant, int $id, int $attachmentId): void
    {
        $attachment = $this->attachment($variant, $id, $attachmentId);
        abort_if($attachment === null, 404);
        Storage::disk('public')->delete((string) $attachment->file_name);
        DB::table('payment_guarantee_attachments')->where('id', $attachmentId)->where('payment_guarantee_id', $id)->delete();
    }

    public function table(string $variant): string
    {
        abort_unless(in_array($variant, $this->variants(), true), 404);

        return $variant === self::STANDARD ? 'payment_guarantee' : 'medical_services_agreement_sadq';
    }

    private function hasLegacyPrivilege(string $permission): bool
    {
        $userId = (int) session('hr_user_id', 0);
        if ($userId < 1) {
            return false;
        }

        return DB::table('user_role as ur')
            ->join('role_perm as rp', 'rp.role_id', '=', 'ur.role_id')
            ->join('permissions as p', 'p.perm_id', '=', 'rp.perm_id')
            ->where('ur.user_id', $userId)
            ->where('p.perm_desc', $permission)
            ->exists();
    }

    private function branchId(): int
    {
        return (int) session('hr_branch_id', 0);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }
}

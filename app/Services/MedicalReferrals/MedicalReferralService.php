<?php

namespace App\Services\MedicalReferrals;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MedicalReferralService
{
    public const BRANCH_ID = 1;

    private const TYPES = [
        'bed-reservation' => ['table' => 'bed_reservation', 'title' => 'إشعار حجز سرير'],
        'accept-referral' => ['table' => 'accept_referral', 'title' => 'إشعار قبول إحالة'],
        'referral-apology' => ['table' => 'ehala_case_apology', 'title' => 'إشعار إعتذار قبول حالة'],
        'crisis-management' => ['table' => 'crisis_management', 'title' => 'إشعار إعتذار إدارة الأزمات الصحية'],
        'red-crescent' => ['table' => 'red_crescent', 'title' => 'إشعار إعتذار الهلال الأحمر'],
        'pulse-status' => ['table' => 'Pulseـstatus', 'title' => 'إشعار بحالة رجوع النبض'],
    ];

    public function definition(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type] + ['type' => $type];
    }

    public function authorizeBranch(): void
    {
        abort_unless(
            (int) session('hr_branch_id', 0) === self::BRANCH_ID
            && (int) session('companies_groups_id', 0) > 0,
            403
        );
    }

    public function list(string $type, array $filters): LengthAwarePaginator
    {
        $definition = $this->definition($type);
        $this->authorizeBranch();
        $query = DB::table($definition['table'].' as records')
            ->where('records.branch_id', self::BRANCH_ID)
            ->where($type === 'pulse-status' ? 'records.group_id' : 'records.companies_groups_id', $this->companyId())
            ->orderByDesc('records.id');

        if ($type === 'pulse-status') {
            $query->leftJoin('incident_report_form_doctors as doctors', 'doctors.id', '=', 'records.doctor')
                ->select('records.*', 'doctors.name as doctor_name');
        } else {
            $query->leftJoin('ra_users as users', 'users.hr_id', '=', 'records.user_id')
                ->select('records.*', 'users.hr_first_name as creator_name');
        }

        if ($filters['from'] !== '') {
            $column = $type === 'pulse-status' ? 'records.create_at' : 'records.date';
            $type === 'pulse-status'
                ? $query->where($column, '>=', $filters['from'].' 00:00:00')
                : $query->where($column, '>=', (string) strtotime($filters['from'].' 00:00:00'));
        }
        if ($filters['to'] !== '') {
            $column = $type === 'pulse-status' ? 'records.create_at' : 'records.date';
            $type === 'pulse-status'
                ? $query->where($column, '<=', $filters['to'].' 23:59:59')
                : $query->where($column, '<=', (string) strtotime($filters['to'].' 23:59:59'));
        }
        if ($filters['identity'] !== '' && in_array($type, ['bed-reservation', 'accept-referral', 'referral-apology'], true)) {
            $query->where('records.idno', 'like', '%'.$filters['identity'].'%');
        }
        if ($filters['room_type'] > 0 && in_array($type, ['bed-reservation', 'accept-referral', 'crisis-management', 'red-crescent'], true)) {
            $query->where('records.room_type', $filters['room_type']);
        }
        if ($filters['user_id'] > 0 && in_array($type, ['bed-reservation', 'accept-referral'], true)) {
            $query->where('records.user_id', $filters['user_id']);
        }
        if ($filters['apology'] > 0 && in_array($type, ['referral-apology', 'crisis-management', 'red-crescent'], true)) {
            $query->where('records.apology', $filters['apology']);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function options(): array
    {
        $this->authorizeBranch();

        return [
            'rooms' => DB::table('room_type')->where('publish', 1)->orderBy('name_ar')->get(),
            'periods' => DB::table('booking_period')->where('publish', 1)->orderBy('id')->get(),
            'apologies' => DB::table('ehala_case_apology_type')->where('publish', 1)->orderBy('id')->get(),
            'countries' => DB::table('countries')->where('publish', 1)->orderBy('country_nationality_ar')->get(),
            'doctors' => DB::table('incident_report_form_doctors')->where('publish', 1)->where('companies_groups_id', $this->companyId())->orderBy('name')->get(),
            'users' => DB::table('ra_users')->where('branch_id', self::BRANCH_ID)->where('companies_groups_id', $this->companyId())->where('hr_user_level', 1)->orderBy('hr_first_name')->get(),
        ];
    }

    public function create(string $type, array $data): int
    {
        $definition = $this->definition($type);
        $this->authorizeBranch();
        $this->validateLookups($type, $data);
        $common = [
            'branch_id' => self::BRANCH_ID,
            $type === 'pulse-status' ? 'group_id' : 'companies_groups_id' => $this->companyId(),
            'user_id' => (int) session('hr_user_id', 0),
        ];

        $payload = match ($type) {
            'bed-reservation' => ['patient_name' => trim($data['patient_name']), 'age' => trim($data['age']), 'idno' => trim($data['idno']), 'gender' => (int) $data['gender'], 'room_type' => (int) $data['room_type'], 'doctor' => trim($data['doctor']), 'date' => (string) time(), 'booking_period' => (int) $data['booking_period'], 'letter_side' => trim($data['letter_side']), 'lang' => $data['lang']],
            'accept-referral' => ['patient_name' => trim($data['patient_name']), 'nationality' => (int) $data['nationality'], 'idno' => trim($data['idno']), 'contact_number' => trim($data['contact_number']), 'ehala_number' => trim($data['ehala_number']), 'doctor' => trim($data['doctor']), 'date' => (string) time(), 'booking_period' => (int) $data['booking_period'], 'room_type' => (int) $data['room_type']],
            'referral-apology' => ['patient_name' => trim($data['patient_name']), 'nationality' => (int) $data['nationality'], 'idno' => trim($data['idno']), 'ehala_number' => trim($data['ehala_number']), 'date' => (string) time(), 'apology' => (int) $data['apology']],
            'crisis-management', 'red-crescent' => ['contact_number' => '0', 'date' => (string) time(), 'booking_period' => (int) $data['booking_period'], 'room_type' => (int) $data['room_type'], 'apology' => (int) $data['apology']],
            'pulse-status' => ['name' => trim($data['name']), 'no' => trim($data['no']), 'doctor' => (int) $data['doctor'], 'date_dlivry' => $data['date_dlivry'], 'Notification_date' => $data['Notification_date'], 'status' => 0, 'create_at' => now()->format('Y-m-d H:i:s'), 'Report_number' => trim($data['Report_number'])],
        };

        return (int) DB::table($definition['table'])->insertGetId($common + $payload);
    }

    public function find(string $type, int $id): ?object
    {
        $definition = $this->definition($type);
        $this->authorizeBranch();

        return DB::table($definition['table'])
            ->where('id', $id)
            ->where('branch_id', self::BRANCH_ID)
            ->where($type === 'pulse-status' ? 'group_id' : 'companies_groups_id', $this->companyId())
            ->first();
    }

    public function delete(string $type, int $id): void
    {
        $record = $this->find($type, $id);
        abort_if($record === null, 404);
        abort_unless((int) $record->user_id === (int) session('hr_user_id', 0), 403);
        DB::table($this->definition($type)['table'])->where('id', $id)->delete();
    }

    private function validateLookups(string $type, array $data): void
    {
        foreach ([
            'room_type' => 'room_type',
            'booking_period' => 'booking_period',
            'apology' => 'ehala_case_apology_type',
            'nationality' => 'countries',
        ] as $field => $table) {
            if (isset($data[$field])) {
                abort_unless(DB::table($table)->where('id', $data[$field])->where('publish', 1)->exists(), 422);
            }
        }
        if ($type === 'pulse-status') {
            abort_unless(DB::table('incident_report_form_doctors')->where('id', $data['doctor'])->where('publish', 1)->where('companies_groups_id', $this->companyId())->exists(), 422);
        }
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }
}

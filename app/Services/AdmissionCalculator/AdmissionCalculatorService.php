<?php

namespace App\Services\AdmissionCalculator;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdmissionCalculatorService
{
    public const BRANCH_ID = 1;

    public function authorizeBranch(): void { abort_unless((int) session('hr_branch_id', 0) === self::BRANCH_ID && (int) session('companies_groups_id', 0) > 0, 403); }

    public function list(string $type, array $filters): LengthAwarePaginator
    {
        $this->authorizeBranch();
        $table = $this->table($type);
        $query = DB::table($table)->where('branch_id', self::BRANCH_ID)->where('companies_groups_id', $this->companyId())->orderByDesc('id');
        return $query->when($filters['file_number'] !== '', fn ($q) => $q->where('file_number', 'like', '%'.$filters['file_number'].'%'))
            ->when($filters['from'] !== '', fn ($q) => $q->whereRaw('FROM_UNIXTIME(date) >= ?', [$filters['from'].' 00:00:00']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereRaw('FROM_UNIXTIME(date) <= ?', [$filters['to'].' 23:59:59']))
            ->paginate(15)->withQueryString();
    }

    public function options(): array
    {
        $this->authorizeBranch();
        return ['rooms' => DB::table('admission_rooms')->where('publish', 1)->where('admission_status_id', 2)->orderBy('name_ar')->get(), 'nationalities' => DB::table('admission_nationality')->where('publish', 1)->orderBy('name_ar')->get()];
    }

    public function create(string $type, array $data): int
    {
        $this->authorizeBranch();
        $table = $this->table($type);
        abort_unless(DB::table('admission_rooms')->where('id', $data['room'])->where('publish', 1)->exists(), 422);
        return (int) DB::table($table)->insertGetId(['branch_id' => self::BRANCH_ID, 'companies_groups_id' => $this->companyId(), 'user_id' => (int) session('hr_user_id', 0), 'patient_name' => trim($data['patient_name']), 'file_number' => trim($data['file_number']), 'nationality' => (int) $data['nationality'], 'room' => (int) $data['room'], 'procedurs' => trim((string) ($data['procedurs'] ?? '')), 'doctor' => trim($data['doctor']), 'date' => (string) time(), 'days' => (int) $data['days'], 'discount' => trim((string) ($data['discount'] ?? '0')), 'tools_value' => trim((string) ($data['tools_value'] ?? '0')), 'lang' => (string) ($data['lang'] ?? 'ar'), 'vat' => trim((string) ($data['vat'] ?? '')), 'code_total' => trim((string) ($data['code_total'] ?? '')), 'type' => 0, 'room_price' => trim((string) ($data['room_price'] ?? '0'))]);
    }

    public function find(string $type, int $id): ?object
    {
        $this->authorizeBranch();
        return DB::table($this->table($type).' as c')->leftJoin('admission_rooms as room', 'room.id', '=', 'c.room')->leftJoin('admission_nationality as nationality', 'nationality.id', '=', 'c.nationality')->where('c.id', $id)->where('c.branch_id', self::BRANCH_ID)->where('c.companies_groups_id', $this->companyId())->select('c.*', 'room.name_ar as room_name_ar', 'room.price as room_price_lookup', 'nationality.name_ar as nationality_name_ar')->first();
    }

    public function delete(string $type, int $id): void
    {
        $record = $this->find($type, $id); abort_if($record === null, 404);
        DB::table($this->table($type))->where('id', $id)->where('branch_id', self::BRANCH_ID)->where('companies_groups_id', $this->companyId())->delete();
    }

    public function table(string $type): string { abort_unless(in_array($type, ['standard', 'manual'], true), 404); return $type === 'manual' ? 'manual_admission_calculator' : 'admission_calculator'; }
    private function companyId(): int { return (int) session('companies_groups_id', 0); }
}

<?php

namespace App\Services\SystemAdministration;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReferenceAdminService
{
    private const SPECS = [
        'groups' => ['table' => 'user_groups', 'title' => 'groups', 'fields' => ['name_en', 'name_ar', 'name_ch'], 'scope' => 'global'],
        'job-titles' => ['table' => 'job_titles', 'title' => 'job_titles', 'fields' => ['branch_id', 'name_en', 'name_ar', 'info', 'training_declarations_id'], 'scope' => 'branch'],
        'governmental-services' => ['table' => 'governmental_services_type', 'title' => 'governmental_services', 'fields' => ['platform_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'global'],
        'companies' => ['table' => 'companies_groups', 'title' => 'companies', 'fields' => ['name_en', 'name_ar', 'name_ch', 'logo'], 'scope' => 'global'],
        'branches' => ['table' => 'branches', 'title' => 'branches', 'fields' => ['name_en', 'name_ar', 'email', 'mobile', 'address'], 'scope' => 'company'],
        'departments' => ['table' => 'branches_departments', 'title' => 'departments', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch'],
        'needs' => ['table' => 'branches_needs', 'title' => 'needs', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch'],
        'service-types' => ['table' => 'branches_service_type', 'title' => 'service_types', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch'],
        'complaint-closing-reasons' => ['table' => 'complaint_closing_reasons', 'title' => 'complaint_closing_reasons', 'fields' => ['name_en', 'name_ar', 'name_ch'], 'scope' => 'global'],
        'complaint-letter-receivers' => ['table' => 'complaint_letter_receiver', 'title' => 'complaint_letter_receivers', 'fields' => ['name_en', 'name_ar', 'name_ch'], 'scope' => 'global'],
        'complaint-statuses' => ['table' => 'complaints_status', 'title' => 'complaint_statuses', 'fields' => ['name_en', 'name_ar', 'name_ch'], 'scope' => 'global'],
        'post-types' => ['table' => 'post_type', 'title' => 'post_types', 'fields' => ['name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'global'],
        'medical-terminology' => ['table' => 'medical_terminology', 'title' => 'medical_terminology', 'fields' => ['name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'global'],
        'service-codes' => ['table' => 'services_codes', 'title' => 'service_codes', 'fields' => ['name_en', 'name_ar', 'price', 'code'], 'scope' => 'global'],
    ];

    public function spec(string $type): array
    {
        abort_unless(isset(self::SPECS[$type]), 404);
        return self::SPECS[$type];
    }

    public function list(string $type, string $search = ''): LengthAwarePaginator
    {
        $spec = $this->spec($type);
        $query = DB::table($spec['table'])->orderByDesc($spec['table'].'.id');
        $this->scope($query, $spec);
        if ($search !== '') {
            $query->where(function ($q) use ($spec, $search): void {
                foreach (array_values(array_intersect($spec['fields'], ['name_ar', 'name_en', 'name_ch', 'info', 'email', 'mobile'])) as $field) $q->orWhere($field, 'like', '%'.$search.'%');
            });
        }
        return $query->paginate(15)->withQueryString();
    }

    public function find(string $type, int $id): object
    {
        $spec = $this->spec($type); $query = DB::table($spec['table'])->where('id', $id); $this->scope($query, $spec); $row = $query->first(); abort_if($row === null, 404); return $row;
    }

    public function options(string $type): array
    {
        return match ($type) {
            'job-titles' => ['branches' => DB::table('branches')->where('companies_groups_id', $this->companyId())->where('publish', 1)->orderBy('name_ar')->get(), 'training' => DB::table('training_declarations')->where('branch_id', $this->branchId())->where('publish', 1)->orderBy('id')->get()],
            'governmental-services' => ['platforms' => DB::table('governmental_services_platforms')->where('publish', 1)->orderBy('name_ar')->get()],
            'departments', 'needs', 'service-types' => ['branches' => DB::table('branches')->where('companies_groups_id', $this->companyId())->where('publish', 1)->orderBy('name_ar')->get()],
            'branches' => ['companies' => DB::table('companies_groups')->where('publish', 1)->orderBy('name_ar')->get()],
            default => [],
        };
    }

    public function create(string $type, array $data): int
    {
        $spec = $this->spec($type); $this->validateScope($spec, $data); $values = $this->values($spec, $data); if ($spec['scope'] === 'company') $values['companies_groups_id'] = $this->companyId(); $values['publish'] = 1; return (int) DB::table($spec['table'])->insertGetId($values);
    }

    public function update(string $type, int $id, array $data): void
    {
        $spec = $this->spec($type); $this->find($type, $id); $this->validateScope($spec, $data); $values = $this->values($spec, $data); if ($spec['scope'] === 'company') $values['companies_groups_id'] = $this->companyId(); DB::table($spec['table'])->where('id', $id)->update($values);
    }

    public function toggle(string $type, int $id): void
    {
        $spec = $this->spec($type); $row = $this->find($type, $id); abort_unless(property_exists($row, 'publish'), 422); DB::table($spec['table'])->where('id', $id)->update(['publish' => (int) ! ((int) $row->publish)]);
    }

    public function delete(string $type, int $id): void
    {
        $spec = $this->spec($type); $this->find($type, $id); if ($type === 'groups' && DB::table('user_groups_permission')->where('groupid', $id)->exists()) abort(422, __('system_administration.reference.in_use')); DB::table($spec['table'])->where('id', $id)->delete();
    }

    private function values(array $spec, array $data): array { return collect($spec['fields'])->mapWithKeys(fn ($field) => [$field => array_key_exists($field, $data) ? trim((string) $data[$field]) : null])->all(); }
    private function validateScope(array $spec, array $data): void { if (in_array($spec['scope'], ['branch', 'company'], true)) abort_unless((int) ($data['branch_id'] ?? $this->branchId()) === $this->branchId() || $spec['scope'] === 'company', 403); if ($spec['scope'] === 'company' && array_key_exists('companies_groups_id', $data)) abort_unless((int) $data['companies_groups_id'] === $this->companyId(), 403); }
    private function scope($query, array $spec): void { if ($spec['scope'] === 'branch') $query->where('branch_id', $this->branchId()); if ($spec['scope'] === 'company') $query->where('companies_groups_id', $this->companyId()); }
    private function branchId(): int { return (int) session('hr_branch_id', 0); }
    private function companyId(): int { return (int) session('companies_groups_id', 0); }
}

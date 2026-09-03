<?php

namespace App\Services\SystemAdministration;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReferenceAdminService
{
    private const SPECS = [
        'groups' => ['table' => 'user_groups', 'title' => 'groups', 'fields' => ['name_en', 'name_ar', 'name_ch'], 'scope' => 'global'],
        'job-titles' => ['table' => 'job_titles', 'title' => 'job_titles', 'fields' => ['branch_id', 'name_en', 'name_ar', 'info', 'training_declarations_id'], 'scope' => 'branch'],
        'governmental-services' => ['table' => 'governmental_services_type', 'title' => 'governmental_services', 'fields' => ['platform_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'global'],
        'companies' => ['table' => 'companies_groups', 'title' => 'companies', 'fields' => ['name_en', 'name_ar', 'name_ch', 'logo'], 'scope' => 'global'],
        'branches' => ['table' => 'branches', 'title' => 'branches', 'fields' => ['name_en', 'name_ar', 'email', 'mobile', 'address'], 'scope' => 'company'],
        'departments' => ['table' => 'branches_departments', 'title' => 'departments', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch'],
        'notice-types' => ['table' => 'notice_type', 'title' => 'notice_types', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch'],
        'needs' => ['table' => 'branches_needs', 'title' => 'needs', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch'],
        'service-types' => ['table' => 'branches_service_type', 'title' => 'service_types', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch'],
        'work-areas' => ['table' => 'branches_area', 'title' => 'work_areas', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch', 'required' => ['branch_id', 'name_en'], 'max_lengths' => ['name_en' => 50, 'name_ar' => 70, 'name_ch' => 70, 'info' => 255]],
        'inquiries' => ['table' => 'inquiries', 'title' => 'inquiry_types', 'fields' => ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'branch', 'required' => ['branch_id', 'name_en', 'name_ar'], 'max_lengths' => ['name_en' => 150, 'name_ar' => 150, 'name_ch' => 70, 'info' => 255]],
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
            $availableFields = array_intersect($this->columns($spec['table']), $spec['fields']);
            $query->where(function ($q) use ($availableFields, $search): void {
                foreach (array_values(array_intersect($availableFields, ['name_ar', 'name_en', 'name_ch', 'info', 'email', 'mobile'])) as $field) $q->orWhere($field, 'like', '%'.$search.'%');
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
            'job-titles' => ['branches' => $this->branchOptions(), 'training' => DB::table('training_declarations')->where('branch_id', $this->branchId())->where('publish', 1)->orderBy('id')->get()],
            'governmental-services' => ['platforms' => DB::table('governmental_services_platforms')->where('publish', 1)->orderBy('name_ar')->get()],
            'departments', 'needs', 'service-types', 'work-areas', 'inquiries' => ['branches' => $this->branchOptions()],
            'branches' => ['companies' => DB::table('companies_groups')
                ->when(Schema::hasColumn('companies_groups', 'publish'), fn ($query) => $query->where('publish', 1))
                ->orderBy('name_ar')->get()],
            default => [],
        };
    }

    private function branchOptions(): \Illuminate\Support\Collection
    {
        return DB::table('branches')
            ->where('companies_groups_id', $this->companyId())
            ->when(Schema::hasColumn('branches', 'publish'), fn ($query) => $query->where('publish', 1))
            ->orderBy('name_ar')
            ->get();
    }

    public function create(string $type, array $data): int
    {
        $spec = $this->spec($type); $this->validateScope($spec, $data); $values = $this->values($spec, $data); if ($spec['scope'] === 'company' && in_array('companies_groups_id', $this->columns($spec['table']), true)) $values['companies_groups_id'] = $this->companyId(); if (in_array('publish', $this->columns($spec['table']), true)) $values['publish'] = 1; return (int) DB::table($spec['table'])->insertGetId($values);
    }

    public function update(string $type, int $id, array $data): void
    {
        $spec = $this->spec($type); $this->find($type, $id); $this->validateScope($spec, $data); $values = $this->values($spec, $data); if ($spec['scope'] === 'company' && in_array('companies_groups_id', $this->columns($spec['table']), true)) $values['companies_groups_id'] = $this->companyId(); DB::table($spec['table'])->where('id', $id)->update($values);
    }

    public function toggle(string $type, int $id): void
    {
        $spec = $this->spec($type); $row = $this->find($type, $id); abort_unless(property_exists($row, 'publish'), 422); DB::table($spec['table'])->where('id', $id)->update(['publish' => (int) ! ((int) $row->publish)]);
    }

    public function delete(string $type, int $id): void
    {
        $spec = $this->spec($type); $this->find($type, $id); if ($type === 'groups' && DB::table('user_groups_permission')->where('groupid', $id)->exists()) abort(422, __('system_administration.reference.in_use')); DB::table($spec['table'])->where('id', $id)->delete();
    }

    public function groupPermissionData(int $group): array
    {
        abort_unless($this->spec('groups')['table'] !== '', 404);
        $groupRow = $this->find('groups', $group);
        $selected = DB::table('user_groups_permission')->where('groupid', $group)->whereNotIn('permit', ['', '0'])->pluck('page')->all();
        $catalog = collect();

        if (DB::getSchemaBuilder()->hasTable('page')) {
            $catalog = DB::table('page')->select(['page', 'name_ar', 'name_en', 'group'])->orderBy('group')->orderBy('arrange')->get();
        }

        $existing = DB::table('user_groups_permission')->where('groupid', $group)->pluck('page')->filter()->unique();
        $missing = $existing->diff($catalog->pluck('page'))->map(fn ($page) => (object) ['page' => $page, 'name_ar' => $page, 'name_en' => $page, 'group' => 0]);

        return ['group' => $groupRow, 'catalog' => $catalog->concat($missing)->values(), 'selected' => $selected];
    }

    public function replaceGroupPermissions(int $group, array $permissions): void
    {
        $this->find('groups', $group);
        $permissions = collect($permissions)->filter()->unique()->values();
        $known = collect();
        if (DB::getSchemaBuilder()->hasTable('page')) {
            $known = DB::table('page')->whereIn('page', $permissions)->pluck('page');
        }
        $known = $known->concat(DB::table('user_groups_permission')->where('groupid', $group)->whereIn('page', $permissions)->pluck('page'))->unique();
        abort_unless($known->count() === $permissions->count(), 422, 'صلاحية غير معروفة.');

        DB::transaction(function () use ($group, $permissions): void {
            DB::table('user_groups_permission')->where('groupid', $group)->delete();
            foreach ($permissions as $page) {
                DB::table('user_groups_permission')->insert(['groupid' => $group, 'page' => $page, 'permit' => '2']);
            }
        });
    }

    private function values(array $spec, array $data): array
    {
        return collect(array_intersect($spec['fields'], $this->columns($spec['table'])))
            ->mapWithKeys(fn ($field) => [$field => array_key_exists($field, $data) ? trim((string) $data[$field]) : null])
            ->all();
    }

    private function columns(string $table): array
    {
        return Schema::getColumnListing($table);
    }
    private function validateScope(array $spec, array $data): void
    {
        if ($spec['scope'] === 'branch' && in_array('branch_id', $this->columns($spec['table']), true)) {
            $branchId = (int) ($data['branch_id'] ?? $this->branchId());
            abort_unless($this->branchId() > 0 && $branchId === $this->branchId(), 403);
            abort_unless(DB::table('branches')->where('id', $branchId)->where('companies_groups_id', $this->companyId())->exists(), 403);
        }

        if ($spec['scope'] === 'company' && array_key_exists('companies_groups_id', $data)) {
            abort_unless((int) $data['companies_groups_id'] === $this->companyId(), 403);
        }
    }

    private function scope($query, array $spec): void
    {
        if ($spec['scope'] === 'branch' && in_array('branch_id', $this->columns($spec['table']), true)) {
            $table = $spec['table'];
            $query->where($table.'.branch_id', $this->branchId())
                ->whereExists(fn ($branch) => $branch->selectRaw('1')->from('branches')->whereColumn('branches.id', $table.'.branch_id')->where('branches.companies_groups_id', $this->companyId()));
        }

        if ($spec['scope'] === 'company') {
            $query->where('companies_groups_id', $this->companyId());
        }
    }
    private function branchId(): int { return (int) session('hr_branch_id', 0); }
    private function companyId(): int { return (int) session('companies_groups_id', 0); }
}

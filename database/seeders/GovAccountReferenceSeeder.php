<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GovAccountReferenceSeeder extends Seeder
{
    private const AUTHORITIES = [
        ['name_ar' => 'وزارة الصحة', 'name_en' => 'Ministry of Health'],
        ['name_ar' => 'الهيئة السعودية للتخصصات الصحية', 'name_en' => 'Saudi Commission for Health Specialties'],
    ];

    private const SERVICES = [
        ['authority' => 'Ministry of Health', 'name_ar' => 'التقارير الطبية', 'name_en' => 'Medical Reports'],
        ['authority' => 'Ministry of Health', 'name_ar' => 'الإجازات الطبية', 'name_en' => 'Medical Leave'],
        ['authority' => 'Saudi Commission for Health Specialties', 'name_ar' => 'الرخص الطبية', 'name_en' => 'Medical Licensing'],
    ];

    private const ROLES = [
        ['name_ar' => 'مدقق', 'name_en' => 'Reviewer'],
        ['name_ar' => 'معتمد', 'name_en' => 'Approver'],
        ['name_ar' => 'مشرف', 'name_en' => 'Supervisor'],
    ];

    private const REQUEST_STATUSES = [
        ['code' => 'draft', 'name_ar' => 'مسودة', 'name_en' => 'Draft', 'info' => '#64748b'],
        ['code' => 'awaiting_employee', 'name_ar' => 'بانتظار تعهد الموظف', 'name_en' => 'Awaiting employee', 'info' => '#ca8a04'],
        ['code' => 'under_review', 'name_ar' => 'تحت المراجعة', 'name_en' => 'Under review', 'info' => '#2563eb'],
        ['code' => 'rejected', 'name_ar' => 'مرفوض', 'name_en' => 'Rejected', 'info' => '#dc2626'],
        ['code' => 'approved', 'name_ar' => 'معتمد', 'name_en' => 'Approved', 'info' => '#15803d'],
        ['code' => 'submitted_to_authority', 'name_ar' => 'مرفوع للجهة', 'name_en' => 'Submitted to authority', 'info' => '#7c3aed'],
        ['code' => 'completed', 'name_ar' => 'مكتمل', 'name_en' => 'Completed', 'info' => '#0f766e'],
        ['code' => 'cancelled', 'name_ar' => 'ملغي', 'name_en' => 'Cancelled', 'info' => '#475569'],
    ];

    private const ACCOUNT_STATUSES = [
        ['code' => 'pending', 'name_ar' => 'قيد الانتظار', 'name_en' => 'Pending', 'info' => '#64748b'],
        ['code' => 'active', 'name_ar' => 'نشط', 'name_en' => 'Active', 'info' => '#15803d'],
        ['code' => 'modification_requested', 'name_ar' => 'مطلوب تعديل', 'name_en' => 'Modification requested', 'info' => '#2563eb'],
        ['code' => 'suspension_requested', 'name_ar' => 'مطلوب إيقاف', 'name_en' => 'Suspension requested', 'info' => '#ca8a04'],
        ['code' => 'suspended', 'name_ar' => 'موقوف', 'name_en' => 'Suspended', 'info' => '#ea580c'],
        ['code' => 'closure_requested', 'name_ar' => 'مطلوب إغلاق', 'name_en' => 'Closure requested', 'info' => '#be123c'],
        ['code' => 'closed', 'name_ar' => 'مغلق', 'name_en' => 'Closed', 'info' => '#475569'],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('gov_account_authorities')) {
            $this->command?->warn('Skipped GovAccountReferenceSeeder: run Government Accounts migrations first.');

            return;
        }

        $companyIds = Schema::hasTable('companies_groups')
            ? DB::table('companies_groups')->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : DB::table('ra_users')->distinct()->pluck('companies_groups_id')->map(fn ($id): int => (int) $id)->all();
        $companyIds = $companyIds ?: [1];

        DB::transaction(function () use ($companyIds): void {
            foreach ($companyIds as $companyId) {
                foreach (self::AUTHORITIES as $index => $authority) {
                    $this->upsertReference('gov_account_authorities', $companyId, $authority, ($index + 1) * 10);
                }
                foreach (self::ROLES as $index => $role) {
                    $this->upsertReference('gov_account_roles', $companyId, $role, ($index + 1) * 10);
                }
                foreach (self::SERVICES as $index => $service) {
                    $authorityId = DB::table('gov_account_authorities')
                        ->where('companies_groups_id', $companyId)->where('name_en', $service['authority'])->value('id');
                    DB::table('gov_account_services')->updateOrInsert(
                        ['companies_groups_id' => $companyId, 'authority_id' => $authorityId, 'name_en' => $service['name_en']],
                        ['name_ar' => $service['name_ar'], 'publish' => true, 'ranking' => ($index + 1) * 10, 'created_at' => now(), 'updated_at' => now()],
                    );
                }
                $this->upsertStatuses('gov_account_request_statuses', $companyId, self::REQUEST_STATUSES);
                $this->upsertStatuses('gov_account_statuses', $companyId, self::ACCOUNT_STATUSES);
            }
        });
    }

    private function upsertReference(string $table, int $companyId, array $record, int $ranking): void
    {
        DB::table($table)->updateOrInsert(
            ['companies_groups_id' => $companyId, 'name_en' => $record['name_en']],
            $record + ['publish' => true, 'ranking' => $ranking, 'created_at' => now(), 'updated_at' => now()],
        );
    }

    private function upsertStatuses(string $table, int $companyId, array $statuses): void
    {
        foreach ($statuses as $index => $status) {
            DB::table($table)->updateOrInsert(
                ['companies_groups_id' => $companyId, 'code' => $status['code']],
                $status + ['publish' => true, 'ranking' => ($index + 1) * 10, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}

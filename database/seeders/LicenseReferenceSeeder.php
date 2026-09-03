<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the starter reference catalog for the regulatory-license module.
 *
 * This seeder is deliberately standalone and idempotent. It never truncates
 * a table, deletes an administrator's custom reference, or creates users and
 * licenses. Run it directly with:
 *
 * php artisan db:seed --class=Database\\Seeders\\LicenseReferenceSeeder
 */
class LicenseReferenceSeeder extends Seeder
{
    /** @var list<array{name_ar:string,name_en:string}> */
    private const AUTHORITIES = [
        ['name_ar' => 'وزارة الصحة', 'name_en' => 'Ministry of Health'],
        ['name_ar' => 'الهيئة العامة للغذاء والدواء', 'name_en' => 'Saudi Food and Drug Authority'],
        ['name_ar' => 'الهيئة السعودية للتخصصات الصحية', 'name_en' => 'Saudi Commission for Health Specialties'],
        ['name_ar' => 'الدفاع المدني', 'name_en' => 'Civil Defense'],
        ['name_ar' => 'المركز السعودي لاعتماد المنشآت الصحية', 'name_en' => 'Saudi Central Board for Accreditation of Healthcare Institutions'],
        ['name_ar' => 'الأمانة والبلدية', 'name_en' => 'Municipality'],
    ];

    /** @var list<array{name_ar:string,name_en:string}> */
    private const TYPES = [
        ['name_ar' => 'ترخيص منشأة صحية', 'name_en' => 'Healthcare Facility License'],
        ['name_ar' => 'ترخيص مزاولة مهنة', 'name_en' => 'Professional Practice License'],
        ['name_ar' => 'ترخيص الأدوية والمستحضرات', 'name_en' => 'Pharmaceutical License'],
        ['name_ar' => 'ترخيص الأجهزة والمستلزمات الطبية', 'name_en' => 'Medical Devices and Supplies License'],
        ['name_ar' => 'ترخيص الدفاع المدني', 'name_en' => 'Civil Defense License'],
        ['name_ar' => 'شهادة اعتماد المنشأة', 'name_en' => 'Facility Accreditation Certificate'],
    ];

    /** @var list<array{code:string,name_ar:string,name_en:string,ranking:int}> */
    private const STAGES = [
        ['code' => 'not_started', 'name_ar' => 'لم يبدأ', 'name_en' => 'Not started', 'ranking' => 10],
        ['code' => 'preparing', 'name_ar' => 'جارٍ التجهيز', 'name_en' => 'Preparing', 'ranking' => 20],
        ['code' => 'awaiting_payment', 'name_ar' => 'بانتظار السداد', 'name_en' => 'Awaiting payment', 'ranking' => 30],
        ['code' => 'submitted', 'name_ar' => 'مقدم للجهة', 'name_en' => 'Submitted to authority', 'ranking' => 40],
        ['code' => 'completed', 'name_ar' => 'مكتمل', 'name_en' => 'Completed', 'ranking' => 50],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('license_authorities') || ! Schema::hasTable('license_types')) {
            $this->command?->warn('Skipped LicenseReferenceSeeder: license reference tables do not exist. Run migrations first.');

            return;
        }

        $companyGroupIds = Schema::hasTable('companies_groups')
            ? DB::table('companies_groups')->pluck('id')->map(static fn ($id): int => (int) $id)->all()
            : [1];

        if ($companyGroupIds === []) {
            $this->command?->warn('Skipped LicenseReferenceSeeder: no company groups exist.');

            return;
        }

        DB::transaction(function () use ($companyGroupIds): void {
            foreach ($companyGroupIds as $companyGroupId) {
                foreach (self::AUTHORITIES as $ranking => $authority) {
                    $this->upsertCompanyReference('license_authorities', $companyGroupId, $authority, $ranking + 10);
                }

                foreach (self::TYPES as $ranking => $type) {
                    $this->upsertCompanyReference('license_types', $companyGroupId, $type, $ranking + 10);
                }
            }

            if (Schema::hasTable('license_renewal_stages')) {
                foreach (self::STAGES as $stage) {
                    DB::table('license_renewal_stages')->updateOrInsert(
                        ['code' => $stage['code']],
                        $stage + ['publish' => true, 'created_at' => now(), 'updated_at' => now()],
                    );
                }
            }
        });

        $this->command?->info(sprintf(
            'License reference catalog is ready for %d company group(s).',
            count($companyGroupIds),
        ));
    }

    /** @param array{name_ar:string,name_en:string} $reference */
    private function upsertCompanyReference(string $table, int $companyGroupId, array $reference, int $ranking): void
    {
        DB::table($table)->updateOrInsert(
            [
                'companies_groups_id' => $companyGroupId,
                'name_en' => $reference['name_en'],
            ],
            $reference + ['publish' => true, 'ranking' => $ranking, 'created_at' => now(), 'updated_at' => now()],
        );
    }
}

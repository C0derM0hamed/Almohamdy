<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Seeds a complete, safe starter dataset for the licenses module.
 *
 * The seeder is intentionally independent from DatabaseSeeder so it can be
 * run against an existing production database without creating demo users or
 * touching unrelated modules. All records use stable seed keys and are
 * upserted/checked, so running it repeatedly does not duplicate data.
 * Existing non-seed records are not deleted.
 *
 * Run with:
 * php artisan db:seed --class='Database\\Seeders\\LicenseModuleSeeder' --force
 */
class LicenseModuleSeeder extends Seeder
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

    /** @var list<array{code:string,name_ar:string,name_en:string,info:string,ranking:int}> */
    private const LICENSE_STATUSES = [
        ['code' => 'active', 'name_ar' => 'ساري', 'name_en' => 'Active', 'info' => '#15803d', 'ranking' => 10],
        ['code' => 'near_expiry', 'name_ar' => 'قريب الانتهاء', 'name_en' => 'Near expiry', 'info' => '#ca8a04', 'ranking' => 20],
        ['code' => 'under_renewal', 'name_ar' => 'تحت التجديد', 'name_en' => 'Under renewal', 'info' => '#2563eb', 'ranking' => 30],
        ['code' => 'expired', 'name_ar' => 'منتهي', 'name_en' => 'Expired', 'info' => '#dc2626', 'ranking' => 40],
        ['code' => 'renewed', 'name_ar' => 'تم التجديد', 'name_en' => 'Renewed', 'info' => '#0f766e', 'ranking' => 50],
    ];

    /** @var list<array{code:string,name_ar:string,name_en:string,ranking:int}> */
    private const RENEWAL_STAGES = [
        ['code' => 'not_started', 'name_ar' => 'لم يبدأ', 'name_en' => 'Not started', 'ranking' => 10],
        ['code' => 'preparing', 'name_ar' => 'جارٍ التجهيز', 'name_en' => 'Preparing', 'ranking' => 20],
        ['code' => 'awaiting_payment', 'name_ar' => 'بانتظار السداد', 'name_en' => 'Awaiting payment', 'ranking' => 30],
        ['code' => 'submitted', 'name_ar' => 'مقدم للجهة', 'name_en' => 'Submitted to authority', 'ranking' => 40],
        ['code' => 'completed', 'name_ar' => 'مكتمل', 'name_en' => 'Completed', 'ranking' => 50],
    ];

    /** @var list<array{code:string,name_ar:string,name_en:string,info:string,ranking:int}> */
    private const PAYMENT_STATUSES = [
        ['code' => 'received', 'name_ar' => 'تم استلام الطلب', 'name_en' => 'Received', 'info' => '#2563eb', 'ranking' => 10],
        ['code' => 'in_progress', 'name_ar' => 'تم البدء بإجراءات السداد', 'name_en' => 'Payment in progress', 'info' => '#ca8a04', 'ranking' => 20],
        ['code' => 'needs_documents', 'name_ar' => 'بحاجة إلى مرفقات', 'name_en' => 'Needs documents', 'info' => '#dc2626', 'ranking' => 30],
        ['code' => 'paid', 'name_ar' => 'تم السداد', 'name_en' => 'Paid', 'info' => '#15803d', 'ranking' => 40],
    ];

    /** @var list<array{key:string,type:string,authority:string,status:string,stage:string,expiry_days:int,title:string}> */
    private const SAMPLE_LICENSES = [
        [
            'key' => 'facility',
            'type' => 'Healthcare Facility License',
            'authority' => 'Ministry of Health',
            'status' => 'active',
            'stage' => 'not_started',
            'expiry_days' => 180,
            'title' => 'ترخيص تشغيل المنشأة الصحية - بيانات تعريفية',
        ],
        [
            'key' => 'professional',
            'type' => 'Professional Practice License',
            'authority' => 'Saudi Commission for Health Specialties',
            'status' => 'near_expiry',
            'stage' => 'preparing',
            'expiry_days' => 45,
            'title' => 'ترخيص مزاولة المهنة - بيانات تعريفية',
        ],
        [
            'key' => 'pharmaceutical',
            'type' => 'Pharmaceutical License',
            'authority' => 'Saudi Food and Drug Authority',
            'status' => 'under_renewal',
            'stage' => 'awaiting_payment',
            'expiry_days' => 75,
            'title' => 'ترخيص الأدوية والمستحضرات - بيانات تعريفية',
        ],
        [
            'key' => 'civil-defense',
            'type' => 'Civil Defense License',
            'authority' => 'Civil Defense',
            'status' => 'expired',
            'stage' => 'submitted',
            'expiry_days' => -15,
            'title' => 'ترخيص الدفاع المدني - بيانات تعريفية',
        ],
        [
            'key' => 'accreditation',
            'type' => 'Facility Accreditation Certificate',
            'authority' => 'Saudi Central Board for Accreditation of Healthcare Institutions',
            'status' => 'renewed',
            'stage' => 'completed',
            'expiry_days' => 365,
            'title' => 'شهادة اعتماد المنشأة - بيانات تعريفية',
        ],
    ];

    private const UNDERTAKING_TEXT = 'أتعهد بمتابعة هذا الترخيص وتجديده قبل انتهائه بمدة كافية، واتخاذ الإجراءات اللازمة في وقتها، وتحمل كامل المسؤولية المترتبة على أي تقصير يؤدي إلى انتهاء الترخيص أو تعطل الخدمة أو مخالفة تنظيمية. (سجل تعريفي للموديول)';

    private const SEED_NOTE = 'License module starter data';

    private const SEED_COMMENT = 'هذه ملاحظة تعريفية للتأكد من عمل قسم التعليقات في موديول التراخيص.';

    public function run(): void
    {
        if (! Schema::hasTable('licenses')) {
            $this->command?->warn('Skipped LicenseModuleSeeder: license tables do not exist. Run migrations first.');

            return;
        }

        if (! Schema::hasTable('companies_groups')) {
            $this->command?->warn('Skipped LicenseModuleSeeder: companies_groups table does not exist.');

            return;
        }

        $companyGroupIds = DB::table('companies_groups')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($companyGroupIds === []) {
            $this->command?->warn('Skipped LicenseModuleSeeder: no company groups exist.');

            return;
        }

        $this->seedGlobalCatalog();

        $seededLicenses = 0;
        foreach ($companyGroupIds as $companyGroupId) {
            $users = $this->activeUsers($companyGroupId);
            $branchIds = $this->branchIds($companyGroupId, $users);

            if ($users === []) {
                $this->command?->warn("Skipped starter license records for company group {$companyGroupId}: no active user exists.");

                continue;
            }

            DB::transaction(function () use ($companyGroupId, $users, $branchIds, &$seededLicenses): void {
                $actorId = $this->preferredUserId($users);

                foreach (self::SAMPLE_LICENSES as $sampleIndex => $sample) {
                    $licenseId = $this->seedLicense($companyGroupId, $actorId, $branchIds, $sampleIndex, $sample);
                    $seededLicenses++;
                    $this->seedLicenseRelations($companyGroupId, $licenseId, $actorId, $branchIds, $sample);
                }

                $this->seedEscalationGroup($companyGroupId, $actorId, $users);
            });
        }

        $this->command?->info(sprintf(
            'Complete license module starter data is ready: %d license record(s) across %d company group(s).',
            $seededLicenses,
            count($companyGroupIds),
        ));
    }

    private function seedGlobalCatalog(): void
    {
        $now = now();

        if (Schema::hasTable('license_authorities')) {
            foreach (self::AUTHORITIES as $ranking => $authority) {
                foreach ($this->companyGroupIds() as $companyGroupId) {
                    DB::table('license_authorities')->updateOrInsert(
                        ['companies_groups_id' => $companyGroupId, 'name_en' => $authority['name_en']],
                        $authority + ['publish' => true, 'ranking' => ($ranking + 1) * 10, 'created_at' => $now, 'updated_at' => $now],
                    );
                }
            }
        }

        if (Schema::hasTable('license_types')) {
            foreach (self::TYPES as $ranking => $type) {
                foreach ($this->companyGroupIds() as $companyGroupId) {
                    DB::table('license_types')->updateOrInsert(
                        ['companies_groups_id' => $companyGroupId, 'name_en' => $type['name_en']],
                        $type + ['publish' => true, 'ranking' => ($ranking + 1) * 10, 'created_at' => $now, 'updated_at' => $now],
                    );
                }
            }
        }

        if (Schema::hasTable('license_statuses')) {
            foreach (self::LICENSE_STATUSES as $status) {
                DB::table('license_statuses')->updateOrInsert(
                    ['code' => $status['code']],
                    $status + ['publish' => true],
                );
            }
        }

        if (Schema::hasTable('license_renewal_stages')) {
            foreach (self::RENEWAL_STAGES as $stage) {
                DB::table('license_renewal_stages')->updateOrInsert(
                    ['code' => $stage['code']],
                    $stage + ['publish' => true, 'created_at' => $now, 'updated_at' => $now],
                );
            }
        }

        if (Schema::hasTable('license_payment_request_statuses')) {
            foreach (self::PAYMENT_STATUSES as $status) {
                DB::table('license_payment_request_statuses')->updateOrInsert(
                    ['code' => $status['code']],
                    $status + ['publish' => true],
                );
            }
        }
    }

    /** @return list<int> */
    private function companyGroupIds(): array
    {
        return DB::table('companies_groups')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /** @return list<object> */
    private function activeUsers(int $companyGroupId): array
    {
        if (! Schema::hasTable('ra_users')) {
            return [];
        }

        return DB::table('ra_users')
            ->where('companies_groups_id', $companyGroupId)
            ->where('activated', 1)
            ->orderByDesc('hr_user_level')
            ->orderBy('hr_id')
            ->get()
            ->all();
    }

    /** @param list<object> $users @return list<int> */
    private function branchIds(int $companyGroupId, array $users): array
    {
        if (! Schema::hasTable('branches')) {
            return [];
        }

        $branchIds = DB::table('branches')
            ->where('companies_groups_id', $companyGroupId)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($branchIds !== []) {
            return $branchIds;
        }

        $fallbackBranchId = (int) ($users[0]->branch_id ?? 0);

        return $fallbackBranchId > 0 ? [$fallbackBranchId] : [];
    }

    /** @param list<object> $users */
    private function preferredUserId(array $users): int
    {
        foreach ($users as $user) {
            if ((int) ($user->hr_user_level ?? 0) === 3) {
                return (int) $user->hr_id;
            }
        }

        return (int) $users[0]->hr_id;
    }

    /** @param list<int> $branchIds @param array{key:string,type:string,authority:string,status:string,stage:string,expiry_days:int,title:string} $sample */
    private function seedLicense(int $companyGroupId, int $actorId, array $branchIds, int $sampleIndex, array $sample): int
    {
        $now = CarbonImmutable::now();
        $licenseNumber = sprintf('SEED-LIC-%d-%s', $companyGroupId, strtoupper(str_replace('-', '', $sample['key'])));
        $authorityId = (int) DB::table('license_authorities')
            ->where('companies_groups_id', $companyGroupId)
            ->where('name_en', $sample['authority'])
            ->value('id');
        $typeId = (int) DB::table('license_types')
            ->where('companies_groups_id', $companyGroupId)
            ->where('name_en', $sample['type'])
            ->value('id');
        $statusId = (int) DB::table('license_statuses')->where('code', $sample['status'])->value('id');
        $stageId = (int) DB::table('license_renewal_stages')->where('code', $sample['stage'])->value('id');

        DB::table('licenses')->updateOrInsert(
            ['companies_groups_id' => $companyGroupId, 'license_number' => $licenseNumber],
            [
                'license_authority_id' => $authorityId,
                'license_type_id' => $typeId,
                'license_number' => $licenseNumber,
                'title' => $sample['title'],
                'responsible_user_id' => $actorId,
                'issue_date' => $now->subMonths(6)->toDateString(),
                'expiry_date' => $now->addDays($sample['expiry_days'])->toDateString(),
                'status_id' => $statusId,
                'renewal_stage_id' => $stageId > 0 ? $stageId : null,
                'notes' => 'بيانات تشغيلية تعريفية لموديول التراخيص.',
                'publish' => true,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $licenseId = (int) DB::table('licenses')
            ->where('companies_groups_id', $companyGroupId)
            ->where('license_number', $licenseNumber)
            ->value('id');

        if (Schema::hasTable('license_branches')) {
            foreach ($branchIds as $branchId) {
                DB::table('license_branches')->updateOrInsert(
                    ['license_id' => $licenseId, 'branch_id' => $branchId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }

        return $licenseId;
    }

    /** @param list<int> $branchIds @param array{key:string,type:string,authority:string,status:string,stage:string,expiry_days:int,title:string} $sample */
    private function seedLicenseRelations(int $companyGroupId, int $licenseId, int $actorId, array $branchIds, array $sample): void
    {
        $now = CarbonImmutable::now();

        if (Schema::hasTable('license_undertakings')) {
            $this->seedUndertaking($licenseId, $actorId, $now);
        }

        $renewalId = null;
        if (Schema::hasTable('license_renewals') && in_array($sample['key'], ['pharmaceutical', 'accreditation'], true)) {
            $renewalId = $this->seedRenewal($licenseId, $sample, $actorId, $now);
        }

        $paymentRequestId = null;
        if (Schema::hasTable('license_payment_requests') && $sample['key'] === 'pharmaceutical') {
            $paymentRequestId = $this->seedPaymentRequest($companyGroupId, $licenseId, $renewalId, $actorId, $now);
        }

        if (Schema::hasTable('license_comments')) {
            $this->seedComment($licenseId, $actorId, $now);
        }

        if (Schema::hasTable('license_timeline')) {
            $this->seedTimeline($licenseId, $actorId, $branchIds[0] ?? null, 'created', 'تم إنشاء سجل تعريفي لموديول التراخيص.', $now);
            if ($renewalId !== null) {
                $this->seedTimeline($licenseId, $actorId, $branchIds[0] ?? null, 'renewal_started', 'تم تسجيل دورة تجديد تعريفية.', $now->addMinute());
            }
            if ($paymentRequestId !== null) {
                $this->seedTimeline($licenseId, $actorId, $branchIds[0] ?? null, 'payment_created', 'تم تسجيل طلب سداد تعريفي.', $now->addMinutes(2));
            }
        }

        if (Schema::hasTable('license_attachments')) {
            $this->seedAttachment($companyGroupId, $licenseId, null, null, 'license', 'license-document', $actorId, $now);
            if ($renewalId !== null && $sample['key'] === 'accreditation') {
                $this->seedAttachment($companyGroupId, $licenseId, $renewalId, null, 'renewal', 'renewal-document', $actorId, $now);
            }
            if ($paymentRequestId !== null) {
                $this->seedAttachment($companyGroupId, $licenseId, null, $paymentRequestId, 'payment', 'payment-document', $actorId, $now);
            }
        }

        if (Schema::hasTable('license_notifications')) {
            $this->seedNotification($licenseId, $paymentRequestId, $actorId, $now);
        }
    }

    private function seedUndertaking(int $licenseId, int $userId, CarbonImmutable $now): void
    {
        $query = DB::table('license_undertakings')
            ->where('license_id', $licenseId)
            ->where('user_id', $userId)
            ->where('undertaking_text', self::UNDERTAKING_TEXT);
        $existingId = $query->value('id');

        $values = [
            'undertaking_text' => self::UNDERTAKING_TEXT,
            'status' => 'accepted',
            'requested_at' => $now,
            'accepted_at' => $now,
            'escalated_at' => null,
            'ip' => '127.0.0.1',
            'user_agent' => 'LicenseModuleSeeder',
            'updated_at' => $now,
        ];

        if ($existingId !== null) {
            DB::table('license_undertakings')->where('id', $existingId)->update($values);

            return;
        }

        DB::table('license_undertakings')->insert($values + [
            'license_id' => $licenseId,
            'user_id' => $userId,
            'created_at' => $now,
        ]);
    }

    /** @param array{key:string,type:string,authority:string,status:string,stage:string,expiry_days:int,title:string} $sample */
    private function seedRenewal(int $licenseId, array $sample, int $actorId, CarbonImmutable $now): int
    {
        $license = DB::table('licenses')->where('id', $licenseId)->first(['expiry_date']);
        $expiryDate = CarbonImmutable::parse((string) $license->expiry_date);
        $isOpen = $sample['key'] === 'pharmaceutical';
        $renewalNotes = self::SEED_NOTE.' - '.$sample['key'];

        $values = [
            'previous_expiry_date' => $isOpen ? $expiryDate->toDateString() : $expiryDate->subYear()->toDateString(),
            'new_expiry_date' => $isOpen ? null : $expiryDate->toDateString(),
            'started_at' => $now,
            'completed_at' => $isOpen ? null : $now,
            'completed_by' => $isOpen ? null : $actorId,
            'notes' => $renewalNotes,
            'updated_at' => $now,
        ];

        $existingId = DB::table('license_renewals')
            ->where('license_id', $licenseId)
            ->where('notes', $renewalNotes)
            ->value('id');

        if ($existingId !== null) {
            DB::table('license_renewals')->where('id', $existingId)->update($values);

            return (int) $existingId;
        }

        return (int) DB::table('license_renewals')->insertGetId($values + [
            'license_id' => $licenseId,
            'created_at' => $now,
        ]);
    }

    private function seedPaymentRequest(int $companyGroupId, int $licenseId, ?int $renewalId, int $actorId, CarbonImmutable $now): int
    {
        $invoiceNumber = sprintf('SEED-PAY-%d-PHARMACEUTICAL', $companyGroupId);
        $statusId = (int) DB::table('license_payment_request_statuses')->where('code', 'received')->value('id');
        $values = [
            'license_id' => $licenseId,
            'renewal_id' => $renewalId,
            'amount' => 1250.00,
            'currency' => 'SAR',
            'bank_name' => 'حساب سداد التراخيص',
            'account_iban' => null,
            'transfer_details' => 'طلب سداد تعريفي لاختبار شاشة الطلبات والإجراءات.',
            'invoice_number' => $invoiceNumber,
            'notes' => 'بيانات تعريفية لموديول التراخيص.',
            'status_id' => $statusId,
            'requested_by' => $actorId,
            'closed_at' => null,
            'updated_at' => $now,
        ];
        $existingId = DB::table('license_payment_requests')->where('invoice_number', $invoiceNumber)->value('id');

        if ($existingId !== null) {
            DB::table('license_payment_requests')->where('id', $existingId)->update($values);
            $paymentRequestId = (int) $existingId;
        } else {
            $paymentRequestId = (int) DB::table('license_payment_requests')->insertGetId($values + ['created_at' => $now]);
        }

        if (Schema::hasTable('license_payment_events')) {
            $eventValues = [
                'status_id' => $statusId,
                'comment' => 'تم إنشاء طلب السداد التعريفي.',
                'event_type' => 'created',
                'created_by' => $actorId,
                'created_at' => $now,
            ];
            $existingEventId = DB::table('license_payment_events')
                ->where('payment_request_id', $paymentRequestId)
                ->where('event_type', 'created')
                ->value('id');

            if ($existingEventId !== null) {
                DB::table('license_payment_events')->where('id', $existingEventId)->update($eventValues);
            } else {
                DB::table('license_payment_events')->insert($eventValues + [
                    'payment_request_id' => $paymentRequestId,
                ]);
            }
        }

        return $paymentRequestId;
    }

    private function seedComment(int $licenseId, int $actorId, CarbonImmutable $now): void
    {
        if (DB::table('license_comments')->where('license_id', $licenseId)->where('body', self::SEED_COMMENT)->exists()) {
            return;
        }

        DB::table('license_comments')->insert([
            'license_id' => $licenseId,
            'user_id' => $actorId,
            'body' => self::SEED_COMMENT,
            'publish' => true,
            'created_at' => $now,
        ]);
    }

    private function seedTimeline(int $licenseId, int $actorId, ?int $branchId, string $eventType, string $notice, CarbonImmutable $date): void
    {
        if (DB::table('license_timeline')->where('license_id', $licenseId)->where('event_type', $eventType)->where('notice', $notice)->exists()) {
            return;
        }

        $statusId = DB::table('licenses')->where('id', $licenseId)->value('status_id');
        DB::table('license_timeline')->insert([
            'license_id' => $licenseId,
            'event_type' => $eventType,
            'status_id' => $statusId,
            'notice' => $notice,
            'meta' => json_encode(['seed' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_by' => $actorId,
            'created_by_type' => 'user',
            'branch_id' => $branchId,
            'date' => $date,
        ]);
    }

    private function seedAttachment(int $companyGroupId, int $licenseId, ?int $renewalId, ?int $paymentRequestId, string $context, string $key, int $actorId, CarbonImmutable $now): void
    {
        $path = sprintf('licenses/seed/company-%d/license-%d-%s.pdf', $companyGroupId, $licenseId, $key);
        $disk = Storage::disk('local');
        $content = null;
        if (! $disk->exists($path) || (int) ($disk->size($path) ?: 0) < 1000) {
            $content = $this->seedPdfContent($licenseId, $key, 'License module starter document');
            $disk->put($path, $content);
        }

        $size = (int) ($disk->size($path) ?: strlen((string) $content));
        $existingId = DB::table('license_attachments')->where('file_path', $path)->value('id');
        $values = [
            'license_id' => $licenseId,
            'renewal_id' => $renewalId,
            'payment_request_id' => $paymentRequestId,
            'context' => $context,
            'file_path' => $path,
            'original_name' => $key.'.pdf',
            'mime' => 'application/pdf',
            'size' => $size,
            'description' => 'ملف تعريفي منشأ بواسطة Seeder موديول التراخيص.',
            'uploaded_by' => $actorId,
            'uploaded_at' => $now,
        ];

        if ($existingId !== null) {
            DB::table('license_attachments')->where('id', $existingId)->update($values);

            return;
        }

        DB::table('license_attachments')->insert($values);
    }

    private function seedPdfContent(int $licenseId, string $key, string $description): string
    {
        $tempDir = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf(['tempDir' => $tempDir]);
        $safeKey = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeDescription = htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $mpdf->WriteHTML('<h1>License attachment</h1><p>License '.$licenseId.' — '.$safeKey.'</p><p>'.$safeDescription.'</p>');

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function seedNotification(int $licenseId, ?int $paymentRequestId, int $recipientUserId, CarbonImmutable $now): void
    {
        if (DB::table('license_notifications')
            ->where('license_id', $licenseId)
            ->where('payment_request_id', $paymentRequestId)
            ->where('event_type', 'undertaking_requested')
            ->where('recipient_user_id', $recipientUserId)
            ->where('channel', 'inapp')
            ->exists()) {
            return;
        }

        $recipient = Schema::hasTable('ra_users')
            ? DB::table('ra_users')->where('hr_id', $recipientUserId)->first(['hr_email_address', 'mobile'])
            : null;

        DB::table('license_notifications')->insert([
            'license_id' => $licenseId,
            'payment_request_id' => $paymentRequestId,
            'event_type' => 'undertaking_requested',
            'recipient_user_id' => $recipientUserId,
            'recipient_email' => trim((string) ($recipient->hr_email_address ?? '')) ?: null,
            'recipient_mobile' => trim((string) ($recipient->mobile ?? '')) ?: null,
            'channel' => 'inapp',
            'status' => 'logged',
            'error' => null,
            'reason' => 'License module starter data',
            'meta' => json_encode(['seed' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'read_at' => null,
            'created_at' => $now,
        ]);
    }

    /** @param list<object> $users */
    private function seedEscalationGroup(int $companyGroupId, int $actorId, array $users): void
    {
        if (! Schema::hasTable('license_escalation_groups')) {
            return;
        }

        $groupName = 'مسؤولو التراخيص - بيانات تعريفية';
        $now = now();
        $groupId = DB::table('license_escalation_groups')
            ->where('companies_groups_id', $companyGroupId)
            ->where('name', $groupName)
            ->value('id');

        if ($groupId === null) {
            $groupId = DB::table('license_escalation_groups')->insertGetId([
                'companies_groups_id' => $companyGroupId,
                'name' => $groupName,
                'publish' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('license_escalation_groups')->where('id', $groupId)->update([
                'publish' => true,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('license_escalation_group_members')) {
            return;
        }

        $memberIds = array_values(array_unique(array_merge([$actorId], array_map(
            static fn (object $user): int => (int) $user->hr_id,
            array_slice($users, 0, 3),
        ))));

        foreach ($memberIds as $userId) {
            DB::table('license_escalation_group_members')->updateOrInsert(
                ['group_id' => (int) $groupId, 'user_id' => $userId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }
}

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
 * Seeds realistic license demo data for the client review package.
 *
 * Creates review-specific licenses assigned to lic_* demo accounts, covering
 * all main workflows: active, near-expiry alerts, under renewal, expired,
 * pending undertaking, payment requests, timeline, notifications, and escalation.
 *
 * Run after LicenseClientReviewSeeder and LicenseReferenceSeeder:
 * php artisan db:seed --class='Database\\Seeders\\LicenseClientReviewDataSeeder' --force
 */
class LicenseClientReviewDataSeeder extends Seeder
{
    /** Numeric license numbers for client review (digits only). */
    private const LICENSE_NUMBERS = [
        'active-facility' => '9001000001',
        'near-expiry-90' => '9001000002',
        'near-expiry-60' => '9001000003',
        'near-expiry-30' => '9001000004',
        'under-renewal' => '9001000005',
        'expired' => '9001000006',
        'pending-undertaking' => '9001000007',
        'renewed' => '9001000008',
        'pending-mo' => '9001000009',
    ];

    /** @var array<string, string> Legacy review license numbers → numeric replacements. */
    private const LEGACY_LICENSE_NUMBERS = [
        'REVIEW-LIC-ACTIVEFACILITY' => '9001000001',
        'REVIEW-LIC-NEAREXPIRY90' => '9001000002',
        'REVIEW-LIC-NEAREXPIRY60' => '9001000003',
        'REVIEW-LIC-NEAREXPIRY30' => '9001000004',
        'REVIEW-LIC-UNDERRENEWAL' => '9001000005',
        'REVIEW-LIC-EXPIRED' => '9001000006',
        'REVIEW-LIC-PENDINGUNDERTAKING' => '9001000007',
        'REVIEW-LIC-RENEWED' => '9001000008',
        'REVIEW-LIC-PENDINGMO' => '9001000009',
    ];

    /** @var array<string, string> Legacy payment invoice numbers → numeric replacements. */
    private const LEGACY_PAYMENT_INVOICES = [
        'REVIEW-LIC-PAY-NEAREXPIRY30' => '9002000004',
        'REVIEW-LIC-PAY-UNDERRENEWAL' => '9002000005',
        'REVIEW-LIC-PAY-RENEWED' => '9002000008',
    ];

    private const UNDERTAKING_TEXT = 'أتعهد بمتابعة هذا الترخيص وتجديده قبل انتهائه بمدة كافية، واتخاذ الإجراءات اللازمة في وقتها، وتحمل كامل المسؤولية المترتبة على أي تقصير يؤدي إلى انتهاء الترخيص أو تعطل الخدمة أو مخالفة تنظيمية.';

    /** @var list<array{key:string,title:string,type_en:string,authority_en:string,status:string,stage:string,expiry_days:int,undertaking:string,renewal:bool,payment:string|null}> */
    private const REVIEW_LICENSES = [
        [
            'key' => 'active-facility',
            'title' => 'ترخيص تشغيل المنشأة الصحية — فرع الرياض',
            'type_en' => 'Healthcare Facility License',
            'authority_en' => 'Ministry of Health',
            'status' => 'active',
            'stage' => 'not_started',
            'expiry_days' => 200,
            'undertaking' => 'accepted',
            'renewal' => false,
            'payment' => null,
        ],
        [
            'key' => 'near-expiry-90',
            'title' => 'ترخيص مزاولة المهنة — قسم الطوارئ',
            'type_en' => 'Professional Practice License',
            'authority_en' => 'Saudi Commission for Health Specialties',
            'status' => 'near_expiry',
            'stage' => 'preparing',
            'expiry_days' => 85,
            'undertaking' => 'accepted',
            'renewal' => false,
            'payment' => null,
        ],
        [
            'key' => 'near-expiry-60',
            'title' => 'ترخيص الأدوية والمستحضرات — الصيدلية المركزية',
            'type_en' => 'Pharmaceutical License',
            'authority_en' => 'Saudi Food and Drug Authority',
            'status' => 'near_expiry',
            'stage' => 'preparing',
            'expiry_days' => 55,
            'undertaking' => 'accepted',
            'renewal' => false,
            'payment' => null,
        ],
        [
            'key' => 'near-expiry-30',
            'title' => 'ترخيص الأجهزة الطبية — قسم الأشعة',
            'type_en' => 'Medical Devices and Supplies License',
            'authority_en' => 'Saudi Food and Drug Authority',
            'status' => 'near_expiry',
            'stage' => 'awaiting_payment',
            'expiry_days' => 25,
            'undertaking' => 'accepted',
            'renewal' => true,
            'payment' => 'received',
        ],
        [
            'key' => 'under-renewal',
            'title' => 'ترخيص الدفاع المدني — مبنى العيادات الخارجية',
            'type_en' => 'Civil Defense License',
            'authority_en' => 'Civil Defense',
            'status' => 'under_renewal',
            'stage' => 'submitted',
            'expiry_days' => 10,
            'undertaking' => 'accepted',
            'renewal' => true,
            'payment' => 'in_progress',
        ],
        [
            'key' => 'expired',
            'title' => 'شهادة اعتماد المنشأة — اعتماد JCI',
            'type_en' => 'Facility Accreditation Certificate',
            'authority_en' => 'Saudi Central Board for Accreditation of Healthcare Institutions',
            'status' => 'expired',
            'stage' => 'submitted',
            'expiry_days' => -20,
            'undertaking' => 'accepted',
            'renewal' => false,
            'payment' => null,
        ],
        [
            'key' => 'pending-undertaking',
            'title' => 'ترخيص بلدي — محطة الطوارئ الجديدة',
            'type_en' => 'Healthcare Facility License',
            'authority_en' => 'Municipality',
            'status' => 'active',
            'stage' => 'not_started',
            'expiry_days' => 365,
            'undertaking' => 'pending',
            'renewal' => false,
            'payment' => null,
        ],
        [
            'key' => 'renewed',
            'title' => 'ترخيص منشأة صحية — فرع جدة (تم التجديد)',
            'type_en' => 'Healthcare Facility License',
            'authority_en' => 'Ministry of Health',
            'status' => 'renewed',
            'stage' => 'completed',
            'expiry_days' => 340,
            'undertaking' => 'accepted',
            'renewal' => true,
            'payment' => 'paid',
        ],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('licenses')) {
            $this->command?->warn('Skipped LicenseClientReviewDataSeeder: license tables do not exist.');

            return;
        }

        $responsibleId = (int) DB::table('ra_users')->where('hr_username', 'lic_responsible_mohamed')->value('hr_id');
        $ccSuperId = (int) DB::table('ra_users')->where('hr_username', 'lic_cc_super_mohamed')->value('hr_id');
        $financeId = (int) DB::table('ra_users')->where('hr_username', 'lic_finance_mohamed')->value('hr_id');

        if ($responsibleId === 0 || $ccSuperId === 0) {
            $this->command?->warn('Skipped LicenseClientReviewDataSeeder: run LicenseClientReviewSeeder first.');

            return;
        }

        $companyGroupId = (int) (DB::table('ra_users')->where('hr_id', $ccSuperId)->value('companies_groups_id') ?: 1);
        $branchIds = DB::table('branches')->where('companies_groups_id', $companyGroupId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($branchIds === []) {
            $branchIds = [(int) (DB::table('ra_users')->where('hr_id', $ccSuperId)->value('branch_id') ?: 1)];
        }

        $this->call(LicenseReferenceSeeder::class);

        $this->migrateLegacyReviewNumbers();

        $seeded = 0;
        $moResponsibleId = (int) DB::table('ra_users')->where('hr_username', 'lic_mo_responsible')->value('hr_id');
        $moCcSuperId = (int) DB::table('ra_users')->where('hr_username', 'lic_mo_cc_super')->value('hr_id');

        DB::transaction(function () use ($companyGroupId, $responsibleId, $ccSuperId, $financeId, $branchIds, $moResponsibleId, $moCcSuperId, &$seeded): void {
            foreach (self::REVIEW_LICENSES as $sample) {
                $this->seedReviewLicense($companyGroupId, $ccSuperId, $responsibleId, $financeId, $branchIds, $sample);
                $seeded++;
            }

            if ($moResponsibleId > 0 && $moCcSuperId > 0) {
                $this->seedReviewLicense($companyGroupId, $moCcSuperId, $moResponsibleId, $financeId, $branchIds, [
                    'key' => 'pending-mo',
                    'title' => 'ترخيص بلدي — تجربة داخلية (محمد)',
                    'type_en' => 'Healthcare Facility License',
                    'authority_en' => 'Municipality',
                    'status' => 'active',
                    'stage' => 'not_started',
                    'expiry_days' => 300,
                    'undertaking' => 'pending',
                    'renewal' => false,
                    'payment' => null,
                ]);
                $seeded++;
            }

            $this->seedEscalationGroup($companyGroupId, $ccSuperId, [$ccSuperId, $responsibleId, $financeId]);
            $this->seedAlertTimelineEntries($companyGroupId);
        });

        $this->command?->info("License client review demo data ready: {$seeded} licenses.");
    }

    /** @param list<int> $branchIds @param array{key:string,title:string,type_en:string,authority_en:string,status:string,stage:string,expiry_days:int,undertaking:string,renewal:bool,payment:string|null} $sample */
    private function seedReviewLicense(int $companyGroupId, int $creatorId, int $responsibleId, int $financeId, array $branchIds, array $sample): void
    {
        $now = CarbonImmutable::now();
        $licenseNumber = self::LICENSE_NUMBERS[$sample['key']] ?? null;
        if ($licenseNumber === null) {
            throw new \InvalidArgumentException('Missing numeric license number for key: '.$sample['key']);
        }

        $authorityId = (int) DB::table('license_authorities')
            ->where('companies_groups_id', $companyGroupId)->where('name_en', $sample['authority_en'])->value('id');
        $typeId = (int) DB::table('license_types')
            ->where('companies_groups_id', $companyGroupId)->where('name_en', $sample['type_en'])->value('id');
        $statusId = (int) DB::table('license_statuses')->where('code', $sample['status'])->value('id');
        $stageId = (int) DB::table('license_renewal_stages')->where('code', $sample['stage'])->value('id');

        DB::table('licenses')->updateOrInsert(
            ['companies_groups_id' => $companyGroupId, 'license_number' => $licenseNumber],
            [
                'license_authority_id' => $authorityId,
                'license_type_id' => $typeId,
                'title' => $sample['title'],
                'responsible_user_id' => $responsibleId,
                'issue_date' => $now->subMonths(8)->toDateString(),
                'expiry_date' => $now->addDays($sample['expiry_days'])->toDateString(),
                'status_id' => $statusId,
                'renewal_stage_id' => $stageId > 0 ? $stageId : null,
                'notes' => 'بيانات تجريبية لمراجعة العميل — موديول التراخيص.',
                'publish' => true,
                'created_by' => $creatorId,
                'created_at' => $now->subDays(30),
                'updated_at' => $now,
            ],
        );

        $licenseId = (int) DB::table('licenses')
            ->where('companies_groups_id', $companyGroupId)->where('license_number', $licenseNumber)->value('id');

        foreach ($branchIds as $branchId) {
            DB::table('license_branches')->updateOrInsert(
                ['license_id' => $licenseId, 'branch_id' => $branchId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        $this->seedUndertaking($licenseId, $responsibleId, $sample['undertaking'], $now);
        $renewalId = $sample['renewal'] ? $this->seedRenewal($licenseId, $sample, $creatorId, $now) : null;
        $paymentId = $sample['payment'] !== null
            ? $this->seedPayment($licenseId, $renewalId, $sample['payment'], $responsibleId, $financeId, $companyGroupId, $sample['key'], $now)
            : null;

        $this->seedComment($licenseId, $responsibleId, $now);
        $this->seedAttachment($companyGroupId, $licenseId, $renewalId, $paymentId, $creatorId, $sample['key'], $now);
        $this->seedTimelineEvents($licenseId, $creatorId, $responsibleId, $branchIds[0] ?? null, $sample, $now);
        $this->seedNotifications($licenseId, $paymentId, $responsibleId, $creatorId, $sample, $now);
    }

    private function seedUndertaking(int $licenseId, int $userId, string $status, CarbonImmutable $now): void
    {
        $accepted = $status === 'accepted';
        $values = [
            'undertaking_text' => self::UNDERTAKING_TEXT,
            'status' => $accepted ? 'accepted' : 'pending',
            'requested_at' => $now->subDays(5),
            'accepted_at' => $accepted ? $now->subDays(4) : null,
            'escalated_at' => null,
            'ip' => $accepted ? '127.0.0.1' : null,
            'user_agent' => $accepted ? 'LicenseClientReviewDataSeeder' : null,
            'updated_at' => $now,
        ];

        DB::table('license_undertakings')->updateOrInsert(
            ['license_id' => $licenseId, 'user_id' => $userId],
            $values + ['created_at' => $now->subDays(5)],
        );
    }

    /** @param array{key:string,title:string,type_en:string,authority_en:string,status:string,stage:string,expiry_days:int,undertaking:string,renewal:bool,payment:string|null} $sample */
    private function seedRenewal(int $licenseId, array $sample, int $actorId, CarbonImmutable $now): int
    {
        $license = DB::table('licenses')->where('id', $licenseId)->first(['expiry_date']);
        $expiryDate = CarbonImmutable::parse((string) $license->expiry_date);
        $isComplete = $sample['status'] === 'renewed';
        $notes = 'دورة تجديد — '.$sample['key'];

        $values = [
            'previous_expiry_date' => $isComplete ? $expiryDate->subYear()->toDateString() : $expiryDate->toDateString(),
            'new_expiry_date' => $isComplete ? $expiryDate->toDateString() : null,
            'started_at' => $now->subDays(14),
            'completed_at' => $isComplete ? $now->subDays(7) : null,
            'completed_by' => $isComplete ? $actorId : null,
            'notes' => $notes,
            'updated_at' => $now,
        ];

        $existingId = DB::table('license_renewals')->where('license_id', $licenseId)->where('notes', $notes)->value('id');
        if ($existingId !== null) {
            DB::table('license_renewals')->where('id', $existingId)->update($values);

            return (int) $existingId;
        }

        return (int) DB::table('license_renewals')->insertGetId($values + ['license_id' => $licenseId, 'created_at' => $now->subDays(14)]);
    }

    private function seedPayment(int $licenseId, ?int $renewalId, string $statusCode, int $requestedBy, int $financeId, int $companyGroupId, string $key, CarbonImmutable $now): int
    {
        $invoiceNumber = match ($key) {
            'near-expiry-30' => '9002000004',
            'under-renewal' => '9002000005',
            'renewed' => '9002000008',
            default => throw new \InvalidArgumentException('Missing payment invoice for key: '.$key),
        };
        $statusId = (int) DB::table('license_payment_request_statuses')->where('code', $statusCode)->value('id');

        $values = [
            'license_id' => $licenseId,
            'renewal_id' => $renewalId,
            'amount' => 3500.00,
            'currency' => 'SAR',
            'bank_name' => 'البنك الأهلي — حساب التراخيص',
            'transfer_details' => 'طلب سداد رسوم تجديد الترخيص.',
            'invoice_number' => $invoiceNumber,
            'notes' => 'بيانات تجريبية لمراجعة العميل.',
            'status_id' => $statusId,
            'requested_by' => $requestedBy,
            'closed_at' => $statusCode === 'paid' ? $now->subDays(3) : null,
            'updated_at' => $now,
        ];

        $existingId = DB::table('license_payment_requests')->where('invoice_number', $invoiceNumber)->value('id');
        if ($existingId !== null) {
            DB::table('license_payment_requests')->where('id', $existingId)->update($values);
            $paymentId = (int) $existingId;
        } else {
            $paymentId = (int) DB::table('license_payment_requests')->insertGetId($values + ['created_at' => $now->subDays(10)]);
        }

        if (Schema::hasTable('license_payment_events')) {
            $events = [
                ['event_type' => 'created', 'status' => 'received', 'comment' => 'تم إنشاء طلب السداد.', 'by' => $requestedBy, 'days' => 10],
            ];
            if (in_array($statusCode, ['in_progress', 'paid'], true)) {
                $events[] = ['event_type' => 'status_changed', 'status' => 'in_progress', 'comment' => 'تم البدء بإجراءات السداد.', 'by' => $financeId, 'days' => 7];
            }
            if ($statusCode === 'paid') {
                $events[] = ['event_type' => 'status_changed', 'status' => 'paid', 'comment' => 'تم السداد وإرفاق إثبات الدفع.', 'by' => $financeId, 'days' => 3];
            }

            foreach ($events as $event) {
                $eventStatusId = (int) DB::table('license_payment_request_statuses')->where('code', $event['status'])->value('id');
                $eventAt = $now->subDays($event['days']);
                DB::table('license_payment_events')->updateOrInsert(
                    ['payment_request_id' => $paymentId, 'event_type' => $event['event_type'], 'comment' => $event['comment']],
                    ['status_id' => $eventStatusId, 'created_by' => $event['by'], 'created_at' => $eventAt],
                );
            }
        }

        return $paymentId;
    }

    private function seedComment(int $licenseId, int $userId, CarbonImmutable $now): void
    {
        $body = 'تم التواصل مع الجهة المختصة بخصوص متطلبات التجديد — بانتظار الرد.';
        if (DB::table('license_comments')->where('license_id', $licenseId)->where('body', $body)->exists()) {
            return;
        }
        DB::table('license_comments')->insert([
            'license_id' => $licenseId, 'user_id' => $userId, 'body' => $body,
            'publish' => true, 'created_at' => $now->subDays(2),
        ]);
    }

    private function seedAttachment(int $companyGroupId, int $licenseId, ?int $renewalId, ?int $paymentId, int $actorId, string $key, CarbonImmutable $now): void
    {
        $path = sprintf('licenses/review/company-%d/license-%d-%s.pdf', $companyGroupId, $licenseId, $key);
        $disk = Storage::disk('local');
        $content = null;
        if (! $disk->exists($path) || (int) ($disk->size($path) ?: 0) < 1000) {
            $content = $this->seedPdfContent($licenseId, $key, 'License review demo document');
            $disk->put($path, $content);
        }

        DB::table('license_attachments')->updateOrInsert(
            ['file_path' => $path],
            [
                'license_id' => $licenseId, 'renewal_id' => $renewalId, 'payment_request_id' => $paymentId,
                'context' => $paymentId ? 'payment' : ($renewalId ? 'renewal' : 'license'),
                'original_name' => $key.'-document.pdf', 'mime' => 'application/pdf',
                'size' => (int) ($disk->size($path) ?: strlen((string) $content)),
                'description' => 'مرفق تجريبي — مراجعة العميل.',
                'uploaded_by' => $actorId, 'uploaded_at' => $now->subDays(5),
            ],
        );
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

    /** @param array{key:string,title:string,type_en:string,authority_en:string,status:string,stage:string,expiry_days:int,undertaking:string,renewal:bool,payment:string|null} $sample */
    private function seedTimelineEvents(int $licenseId, int $creatorId, int $responsibleId, ?int $branchId, array $sample, CarbonImmutable $now): void
    {
        $events = [
            ['type' => 'created', 'notice' => 'تم إنشاء سجل الترخيص.', 'by' => $creatorId, 'days' => 30],
            ['type' => 'assigned', 'notice' => 'تم تعيين المسؤول عن الترخيص.', 'by' => $creatorId, 'days' => 29],
        ];

        if ($sample['undertaking'] === 'accepted') {
            $events[] = ['type' => 'undertaking_accepted', 'notice' => 'تم قبول التعهد من المسؤول.', 'by' => $responsibleId, 'days' => 26];
        } elseif ($sample['undertaking'] === 'pending') {
            $events[] = ['type' => 'undertaking_requested', 'notice' => 'بانتظار قبول التعهد من المسؤول.', 'by' => $creatorId, 'days' => 5];
        }

        if ($sample['renewal']) {
            $events[] = ['type' => 'renewal_started', 'notice' => 'تم بدء دورة التجديد.', 'by' => $responsibleId, 'days' => 14];
        }
        if ($sample['payment'] !== null) {
            $events[] = ['type' => 'payment_created', 'notice' => 'تم إنشاء طلب سداد.', 'by' => $responsibleId, 'days' => 10];
        }
        if ($sample['status'] === 'renewed') {
            $events[] = ['type' => 'renewal_completed', 'notice' => 'تم إكمال التجديد بنجاح.', 'by' => $responsibleId, 'days' => 7];
        }

        $statusId = DB::table('licenses')->where('id', $licenseId)->value('status_id');
        foreach ($events as $event) {
            DB::table('license_timeline')->updateOrInsert(
                ['license_id' => $licenseId, 'event_type' => $event['type'], 'notice' => $event['notice']],
                [
                    'status_id' => $statusId,
                    'meta' => json_encode(['review' => true], JSON_UNESCAPED_UNICODE),
                    'created_by' => $event['by'], 'created_by_type' => 'user',
                    'branch_id' => $branchId, 'date' => $now->subDays($event['days']),
                ],
            );
        }
    }

    /** @param array{key:string,title:string,type_en:string,authority_en:string,status:string,stage:string,expiry_days:int,undertaking:string,renewal:bool,payment:string|null} $sample */
    private function seedNotifications(int $licenseId, ?int $paymentId, int $responsibleId, int $ccSuperId, array $sample, CarbonImmutable $now): void
    {
        $recipient = DB::table('ra_users')->where('hr_id', $responsibleId)->first(['hr_email_address']);
        $notifications = [];

        if ($sample['undertaking'] === 'pending') {
            $notifications[] = ['event' => 'undertaking_requested', 'recipient' => $responsibleId, 'days' => 5];
        }
        if (in_array($sample['key'], ['near-expiry-90', 'near-expiry-60', 'near-expiry-30'], true)) {
            $notifications[] = ['event' => 'expiry_alert', 'recipient' => $responsibleId, 'days' => 3];
            $notifications[] = ['event' => 'expiry_alert', 'recipient' => $ccSuperId, 'days' => 3];
        }
        if ($paymentId !== null) {
            $notifications[] = ['event' => 'payment_created', 'recipient' => $responsibleId, 'days' => 10];
        }

        foreach ($notifications as $note) {
            DB::table('license_notifications')->updateOrInsert(
                [
                    'license_id' => $licenseId, 'event_type' => $note['event'],
                    'recipient_user_id' => $note['recipient'], 'channel' => 'inapp',
                ],
                [
                    'payment_request_id' => $paymentId,
                    'recipient_email' => $recipient->hr_email_address ?? null,
                    'status' => 'logged', 'reason' => 'License client review demo',
                    'meta' => json_encode(['review' => true], JSON_UNESCAPED_UNICODE),
                    'read_at' => null, 'created_at' => $now->subDays($note['days']),
                ],
            );
        }
    }

    /** @param list<int> $memberIds */
    private function seedEscalationGroup(int $companyGroupId, int $actorId, array $memberIds): void
    {
        if (! Schema::hasTable('license_escalation_groups')) {
            return;
        }

        $groupName = 'مجموعة تصعيد التراخيص — مراجعة العميل';
        $now = now();
        $groupId = DB::table('license_escalation_groups')
            ->where('companies_groups_id', $companyGroupId)->where('name', $groupName)->value('id');

        if ($groupId === null) {
            $groupId = DB::table('license_escalation_groups')->insertGetId([
                'companies_groups_id' => $companyGroupId, 'name' => $groupName,
                'publish' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('license_escalation_group_members')) {
            foreach (array_unique($memberIds) as $userId) {
                DB::table('license_escalation_group_members')->updateOrInsert(
                    ['group_id' => (int) $groupId, 'user_id' => $userId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    private function migrateLegacyReviewNumbers(): void
    {
        foreach (self::LEGACY_LICENSE_NUMBERS as $legacy => $numeric) {
            if (DB::table('licenses')->where('license_number', $legacy)->exists()) {
                DB::table('licenses')->where('license_number', $legacy)->update(['license_number' => $numeric]);
            }
        }

        foreach (self::LEGACY_PAYMENT_INVOICES as $legacy => $numeric) {
            if (DB::table('license_payment_requests')->where('invoice_number', $legacy)->exists()) {
                DB::table('license_payment_requests')->where('invoice_number', $legacy)->update(['invoice_number' => $numeric]);
            }
        }
    }

    private function seedAlertTimelineEntries(int $companyGroupId): void
    {
        $alertNumbers = [
            self::LICENSE_NUMBERS['near-expiry-90'],
            self::LICENSE_NUMBERS['near-expiry-60'],
            self::LICENSE_NUMBERS['near-expiry-30'],
        ];

        $alertLicenses = DB::table('licenses')
            ->where('companies_groups_id', $companyGroupId)
            ->whereIn('license_number', $alertNumbers)
            ->get(['id', 'status_id', 'license_number']);

        $windows = [
            self::LICENSE_NUMBERS['near-expiry-90'] => 'تنبيه: يتبقى 90 يوماً على انتهاء الترخيص.',
            self::LICENSE_NUMBERS['near-expiry-60'] => 'تنبيه: يتبقى 60 يوماً على انتهاء الترخيص.',
            self::LICENSE_NUMBERS['near-expiry-30'] => 'تنبيه: يتبقى 30 يوماً على انتهاء الترخيص.',
        ];

        foreach ($alertLicenses as $license) {
            $number = DB::table('licenses')->where('id', $license->id)->value('license_number');
            $notice = $windows[(string) $number] ?? null;
            if ($notice === null) {
                continue;
            }
            DB::table('license_timeline')->updateOrInsert(
                ['license_id' => $license->id, 'event_type' => 'expiry_alert', 'notice' => $notice],
                [
                    'status_id' => $license->status_id,
                    'meta' => json_encode(['alert_window' => true], JSON_UNESCAPED_UNICODE),
                    'created_by' => null, 'created_by_type' => 'scheduler',
                    'branch_id' => null, 'date' => now()->subDays(1),
                ],
            );
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\LicenseEscalationGroup;
use App\Models\LicenseNotification;
use App\Models\LicensePaymentRequest;
use App\Models\LicensePaymentRequestStatus;
use App\Models\LicenseStatus;
use App\Models\LicenseUndertaking;
use App\Services\Licenses\LicenseDashboardService;
use App\Services\Licenses\LicenseExpiryAlertService;
use App\Services\Licenses\LicenseExportService;
use App\Services\Licenses\LicensePaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LicenseModuleAlertFinanceReportingTest extends LicenseModuleTestCase
{
    public function test_scheduler_processes_all_windows_deduplicates_and_restarts_for_a_new_expiry_snapshot(): void
    {
        $at = CarbonImmutable::parse('2026-01-01 08:00:00');
        CarbonImmutable::setTestNow($at);
        $this->grant(20, 'licenses_admin');
        $group = LicenseEscalationGroup::query()->create([
            'companies_groups_id' => 1, 'name' => 'Critical escalation', 'publish' => true,
        ]);
        $group->users()->sync([12]);

        $yellow = $this->makeLicense(expiryDate: $at->addDays(90)->toDateString(), title: 'Yellow window');
        $sixty = $this->makeLicense(expiryDate: $at->addDays(60)->toDateString(), title: 'Sixty-day window');
        $red = $this->makeLicense(expiryDate: $at->addDays(30)->toDateString(), title: 'Red window');
        $expired = $this->makeLicense(expiryDate: $at->subDay()->toDateString(), title: 'Expired window');

        $service = app(LicenseExpiryAlertService::class);
        $first = $service->run(false, $at);
        $this->assertSame(4, $first['checked']);
        $this->assertSame(4, $first['alerts']);
        $this->assertSame(4, $first['statuses_updated']);
        $this->assertSame('near_expiry', $yellow->fresh()->status?->code);
        $this->assertSame('near_expiry', $sixty->fresh()->status?->code);
        $this->assertSame('near_expiry', $red->fresh()->status?->code);
        $this->assertSame('expired', $expired->fresh()->status?->code);

        foreach ([
            [$yellow, 'expiry_90_days', 'yellow'],
            [$sixty, 'expiry_60_days', 'reminder_sent'],
            [$red, 'expiry_30_days', 'red'],
            [$expired, 'license_expired', 'expired'],
        ] as [$license, $eventType, $timelineType]) {
            $this->assertDatabaseHas('license_notifications', [
                'license_id' => $license->id,
                'recipient_user_id' => 10,
                'event_type' => $eventType,
                'channel' => 'inapp',
            ]);
            $this->assertDatabaseHas('license_timeline', [
                'license_id' => $license->id, 'event_type' => $timelineType,
            ]);
        }
        $this->assertDatabaseHas('license_notifications', [
            'license_id' => $red->id, 'recipient_user_id' => 12,
            'event_type' => 'expiry_30_days', 'channel' => 'inapp',
        ]);

        $before = LicenseNotification::query()->where('channel', 'inapp')->count();
        $second = $service->run(false, $at);
        $this->assertSame(0, $second['alerts']);
        $this->assertSame($before, LicenseNotification::query()->where('channel', 'inapp')->count());

        $newAt = CarbonImmutable::parse('2027-01-01 08:00:00');
        CarbonImmutable::setTestNow($newAt);
        $yellow->update([
            'expiry_date' => $newAt->addDays(90)->toDateString(),
            'status_id' => LicenseStatus::query()->where('code', 'active')->value('id'),
        ]);
        $service->run(false, $newAt);
        $cycles = LicenseNotification::query()->where('license_id', $yellow->id)
            ->where('event_type', 'expiry_90_days')->where('channel', 'inapp')->get();
        // The responsible user and the Corporate Communication supervisor
        // each receive the alert for every distinct expiry cycle.
        $this->assertCount(4, $cycles);
        $this->assertCount(2, $cycles->pluck('meta.expiry_date')->unique());
    }

    public function test_scheduler_escalates_an_overdue_undertaking_once_to_supervisors_and_super_admins(): void
    {
        CarbonImmutable::setTestNow('2026-01-08 09:00:00');
        $this->grant(20, 'licenses_admin');
        $license = $this->makeLicense(expiryDate: '2027-01-01');
        $undertaking = LicenseUndertaking::query()->create([
            'license_id' => $license->id,
            'user_id' => 10,
            'undertaking_text' => 'Responsibility snapshot',
            'status' => 'pending',
            'requested_at' => CarbonImmutable::parse('2026-01-04 09:00:00'),
        ]);

        $service = app(LicenseExpiryAlertService::class);
        $first = $service->run(false, CarbonImmutable::now());
        $this->assertSame(1, $first['undertakings_escalated']);
        $this->assertSame('escalated', $undertaking->fresh()->status);
        $this->assertSame('2026-01-08 09:00:00', $undertaking->fresh()->escalated_at?->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('license_timeline', [
            'license_id' => $license->id, 'event_type' => 'undertaking_escalated',
        ]);
        foreach ([1, 20, 30] as $recipientId) {
            $this->assertDatabaseHas('license_notifications', [
                'license_id' => $license->id,
                'recipient_user_id' => $recipientId,
                'event_type' => 'undertaking_overdue',
                'channel' => 'inapp',
            ]);
        }

        $second = $service->run(false, CarbonImmutable::now());
        $this->assertSame(0, $second['undertakings_escalated']);
        $this->assertSame(3, LicenseNotification::query()->where('license_id', $license->id)
            ->where('event_type', 'undertaking_overdue')->where('channel', 'inapp')->count());
    }

    public function test_finance_workflow_enforces_transitions_and_requires_private_proof_for_paid(): void
    {
        CarbonImmutable::setTestNow('2026-08-30 11:00:00');
        $license = $this->makeLicense(ownerId: 10, expiryDate: '2026-12-31');
        LicenseUndertaking::query()->create([
            'license_id' => $license->id,
            'user_id' => 10,
            'undertaking_text' => 'Accepted responsibility snapshot',
            'status' => 'accepted',
            'requested_at' => now()->subDay(),
            'accepted_at' => now(),
        ]);
        $this->grant(10, 'licenses.view', 'licenses.process');
        $this->grant(20, 'licenses_finance');

        $this->actAsLicenseUser(10);
        $responsiblePayments = app(LicensePaymentService::class);
        $payment = $responsiblePayments->create($license, [
            'amount' => 1500.75,
            'currency' => 'SAR',
            'bank_name' => 'Regulatory Bank',
            'account_iban' => 'SA001234567890',
            'transfer_details' => 'Renewal transfer',
            'invoice_number' => 'INV-100',
            'notes' => 'Please pay before submission',
        ]);
        $this->assertSame('received', $payment->status?->code);
        $this->assertSame(10, (int) $payment->requested_by);
        $this->assertDatabaseHas('license_payment_events', [
            'payment_request_id' => $payment->id, 'event_type' => 'created',
        ]);
        $this->assertDatabaseHas('license_notifications', [
            'license_id' => $license->id, 'payment_request_id' => $payment->id,
            'recipient_user_id' => 20, 'event_type' => 'payment_created', 'channel' => 'inapp',
        ]);

        $this->actAsLicenseUser(20);
        $finance = app(LicensePaymentService::class);
        $inProgress = $finance->updateStatus($payment, 'in_progress', 'Finance started processing');
        $this->assertSame('in_progress', $inProgress->status?->code);
        $this->assertThrows(
            fn () => $finance->updateStatus($inProgress, 'paid', 'Missing proof'),
            ValidationException::class,
        );
        $this->assertSame('in_progress', $inProgress->fresh()->status?->code);

        $proof = UploadedFile::fake()->create('payment-proof.pdf', 20, 'application/pdf');
        $paid = $finance->updateStatus($inProgress, 'paid', 'Paid by bank transfer', $proof);
        $this->assertSame('paid', $paid->status?->code);
        $this->assertNotNull($paid->closed_at);
        $proofRecord = $paid->attachments()->where('context', 'payment_proof')->firstOrFail();
        Storage::disk('local')->assertExists($proofRecord->file_path);
        Storage::disk('public')->assertMissing($proofRecord->file_path);
        $this->assertDatabaseHas('license_payment_events', [
            'payment_request_id' => $payment->id, 'event_type' => 'proof_uploaded',
        ]);
        $this->assertDatabaseHas('license_timeline', [
            'license_id' => $license->id, 'event_type' => 'payment_status_changed',
        ]);
        $this->assertDatabaseHas('license_notifications', [
            'license_id' => $license->id, 'payment_request_id' => $payment->id,
            'recipient_user_id' => 10, 'event_type' => 'payment_status_changed', 'channel' => 'inapp',
        ]);
        $this->assertThrows(
            fn () => $finance->updateStatus($paid, 'received', 'Cannot reopen paid directly'),
            ValidationException::class,
        );
    }

    public function test_dashboard_and_csv_export_respect_the_same_scoped_dataset(): void
    {
        CarbonImmutable::setTestNow('2026-08-30 12:00:00');
        $active = $this->makeLicense(ownerId: 10, branchIds: [1], statusCode: 'active', expiryDate: '2027-01-01', title: 'VISIBLE ACTIVE');
        $near = $this->makeLicense(ownerId: 10, branchIds: [1], statusCode: 'near_expiry', expiryDate: '2026-11-01', title: 'VISIBLE NEAR');
        $renewing = $this->makeLicense(ownerId: 10, branchIds: [1], statusCode: 'under_renewal', expiryDate: '2026-10-01', title: 'VISIBLE RENEWING');
        $expired = $this->makeLicense(ownerId: 10, branchIds: [1], statusCode: 'expired', expiryDate: '2026-08-01', title: 'VISIBLE EXPIRED');
        $otherBranch = $this->makeLicense(ownerId: 10, branchIds: [2], title: 'HIDDEN OTHER BRANCH');
        $otherOwner = $this->makeLicense(ownerId: 11, branchIds: [1], title: 'HIDDEN OTHER OWNER');
        $otherCompany = $this->makeLicense(ownerId: 40, companyId: 2, branchIds: [3], title: 'HIDDEN OTHER COMPANY');

        $receivedStatus = LicensePaymentRequestStatus::query()->where('code', 'received')->value('id');
        $paidStatus = LicensePaymentRequestStatus::query()->where('code', 'paid')->value('id');
        LicensePaymentRequest::query()->create([
            'license_id' => $active->id,
            'amount' => 100,
            'currency' => 'SAR',
            'status_id' => $receivedStatus,
            'requested_by' => 10,
        ]);
        $paid = LicensePaymentRequest::query()->create([
            'license_id' => $near->id,
            'amount' => 200,
            'currency' => 'SAR',
            'status_id' => $paidStatus,
            'requested_by' => 10,
        ]);
        CarbonImmutable::setTestNow('2026-08-30 16:00:00');
        $paid->update(['closed_at' => now()]);

        $this->grant(10, 'licenses.view', 'licenses.process', 'licenses.export');
        $this->actAsLicenseUser(10);
        $metrics = app(LicenseDashboardService::class)->metrics();
        $this->assertSame(4, $metrics['kpis']['total']);
        $this->assertSame(1, $metrics['kpis']['active']);
        $this->assertSame(1, $metrics['kpis']['near_expiry']);
        $this->assertSame(1, $metrics['kpis']['under_renewal']);
        $this->assertSame(1, $metrics['kpis']['expired']);
        $this->assertSame(1, $metrics['finance']['open']);
        $this->assertSame(1, $metrics['finance']['paid']);
        $this->assertSame(4.0, $metrics['finance']['average_close_hours']);
        $this->assertSame(4, (int) $metrics['byBranch']->first()->total);

        $response = app(LicenseExportService::class)->download([], 'csv');
        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();
        foreach ([$active, $near, $renewing, $expired] as $visible) {
            $this->assertStringContainsString((string) $visible->title, $csv);
        }
        foreach ([$otherBranch, $otherOwner, $otherCompany] as $hidden) {
            $this->assertStringNotContainsString((string) $hidden->title, $csv);
        }

        $filtered = app(LicenseDashboardService::class)->metrics(['expiry_from' => '2026-10-15']);
        $this->assertSame(2, $filtered['kpis']['total']);
    }

    public function test_report_exports_and_personal_notification_inbox_remain_license_scoped(): void
    {
        $license = $this->makeLicense(ownerId: 10, branchIds: [1], title: 'Visible export record');
        $otherBranch = $this->makeLicense(ownerId: 10, branchIds: [2], title: 'Hidden export record');
        $this->grant(10, 'licenses.view', 'licenses.process', 'licenses.export');
        $this->actAsLicenseUser(10);

        $paymentStatus = LicensePaymentRequestStatus::query()->where('code', 'received')->value('id');
        LicensePaymentRequest::query()->create([
            'license_id' => $license->id, 'amount' => 250, 'currency' => 'SAR', 'status_id' => $paymentStatus, 'requested_by' => 10,
        ]);
        LicenseNotification::query()->create([
            'license_id' => $license->id, 'event_type' => 'expiry_90_days', 'recipient_user_id' => 10,
            'channel' => 'inapp', 'status' => 'logged', 'reason' => 'Visible alert', 'created_at' => now(),
        ]);
        LicenseNotification::query()->create([
            'license_id' => $otherBranch->id, 'event_type' => 'expiry_90_days', 'recipient_user_id' => 10,
            'channel' => 'inapp', 'status' => 'logged', 'reason' => 'Hidden alert', 'created_at' => now(),
        ]);

        foreach (['payments', 'alerts', 'responsibilities'] as $report) {
            $response = app(LicenseExportService::class)->download(['report' => $report], 'xls');
            ob_start();
            $response->sendContent();
            $content = (string) ob_get_clean();
            $this->assertStringContainsString('Visible export record', $content);
            $this->assertStringNotContainsString('Hidden export record', $content);
        }

        $notifications = app(\App\Services\Licenses\LicenseNotificationService::class);
        $this->assertSame(1, $notifications->unreadCountForCurrentUser());
        $inbox = $notifications->inboxForCurrentUser();
        $this->assertSame(1, $inbox->total());
        $notificationId = (int) $inbox->first()->id;
        $this->withSession([
            'hr_user_id' => 10, 'hr_user_level' => 1, 'hr_branch_id' => 1,
            'companies_groups_id' => 1, 'groupid' => 10,
        ])->get(route('modules.licenses.notifications'))
            ->assertOk()->assertSee($license->license_number)->assertDontSee($otherBranch->license_number);
        $notifications->markReadForCurrentUser($notificationId);
        $this->assertSame(0, $notifications->unreadCountForCurrentUser());
    }
}

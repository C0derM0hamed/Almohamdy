<?php

namespace Tests\Feature;

use App\Http\Requests\Licenses\StoreLicenseAttachmentRequest;
use App\Models\LicenseAttachment;
use App\Models\LicenseNotification;
use App\Models\LicenseRenewal;
use App\Models\LicenseStatus;
use App\Models\LicenseUndertaking;
use App\Repositories\Licenses\LicenseRepository;
use App\Services\Licenses\LicenseService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LicenseModuleDomainWorkflowTest extends LicenseModuleTestCase
{
    public function test_module_migrations_are_idempotent_seed_lookups_and_have_a_real_rollback(): void
    {
        foreach ($this->licenseMigrations as $migration) {
            $migration->up();
        }

        $this->assertSame(
            ['active', 'expired', 'near_expiry', 'renewed', 'under_renewal'],
            DB::table('license_statuses')->orderBy('code')->pluck('code')->all(),
        );
        $this->assertSame(5, DB::table('license_renewal_stages')->count());
        $this->assertSame(4, DB::table('license_payment_request_statuses')->count());

        $status = LicenseStatus::query()->where('code', 'near_expiry')->firstOrFail();
        app()->setLocale('en');
        $this->assertSame('Near expiry', $status->localizedName());
        app()->setLocale('ar');
        $this->assertSame('قريب الانتهاء', $status->localizedName());
        $this->assertSame('#ca8a04', $status->badgeColor());

        foreach (array_reverse($this->licenseMigrations) as $migration) {
            $migration->down();
        }

        foreach ([
            'license_notifications', 'license_escalation_group_members', 'license_escalation_groups',
            'license_timeline', 'license_attachments', 'license_comments', 'license_payment_events',
            'license_payment_requests', 'license_payment_request_statuses', 'license_renewals',
            'license_undertakings', 'license_branches', 'licenses', 'license_renewal_stages',
            'license_statuses', 'license_types', 'license_authorities',
        ] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table.' was not rolled back');
        }
    }

    public function test_repository_enforces_company_branch_and_assignment_scopes(): void
    {
        $owned = $this->makeLicense(ownerId: 10, branchIds: [1], title: 'Owned and visible');
        $otherOwner = $this->makeLicense(ownerId: 11, branchIds: [1], title: 'Other owner');
        $otherBranch = $this->makeLicense(ownerId: 10, branchIds: [2], title: 'Other branch');
        $multiBranch = $this->makeLicense(ownerId: 10, branchIds: [1, 2], title: 'Multi branch');
        $otherCompany = $this->makeLicense(ownerId: 40, companyId: 2, branchIds: [3], title: 'Other company');

        $this->grant(10, 'licenses.view', 'licenses.process');
        $this->actAsLicenseUser(10);
        $visibleIds = app(LicenseRepository::class)->scopedQuery()->orderBy('id')->pluck('id')->all();

        $this->assertSame([$owned->id, $multiBranch->id], $visibleIds);
        $this->assertNotContains($otherOwner->id, $visibleIds);
        $this->assertNotContains($otherBranch->id, $visibleIds);
        $this->assertNotContains($otherCompany->id, $visibleIds);

        $this->actAsLicenseUser(1);
        $adminVisible = app(LicenseRepository::class)->scopedQuery()->pluck('id')->all();
        $this->assertContains($owned->id, $adminVisible);
        $this->assertContains($otherOwner->id, $adminVisible);
        $this->assertContains($otherBranch->id, $adminVisible);
        $this->assertNotContains($otherCompany->id, $adminVisible);
    }

    public function test_creation_reassignment_and_undertaking_acceptance_gate_the_responsible_user(): void
    {
        CarbonImmutable::setTestNow('2026-08-30 09:00:00');
        $this->actAsLicenseUser(1);
        $service = app(LicenseService::class);
        $authorityId = DB::table('license_authorities')->insertGetId([
            'companies_groups_id' => 1, 'name_ar' => 'وزارة الصحة', 'name_en' => 'Ministry of Health',
            'publish' => true, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $typeId = DB::table('license_types')->insertGetId([
            'companies_groups_id' => 1, 'name_ar' => 'تشغيل', 'name_en' => 'Operation',
            'publish' => true, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $license = $service->store([
            'license_authority_id' => $authorityId,
            'license_type_id' => $typeId,
            'license_number' => '100001',
            'title' => 'Hospital operation permit',
            'responsible_user_id' => 10,
            'issue_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'renewal_stage_id' => null,
            'notes' => null,
            'branch_ids' => [1, 2],
        ]);

        $this->assertSame([1, 2], $license->branches->pluck('id')->sort()->values()->all());
        $this->assertDatabaseHas('license_undertakings', [
            'license_id' => $license->id, 'user_id' => 10, 'status' => 'pending',
        ]);
        $snapshot = LicenseUndertaking::query()->where('license_id', $license->id)->firstOrFail()->undertaking_text;
        $this->assertNotSame('', trim($snapshot));
        $this->assertDatabaseHas('license_timeline', ['license_id' => $license->id, 'event_type' => 'created']);
        $this->assertDatabaseHas('license_notifications', [
            'license_id' => $license->id, 'recipient_user_id' => 10,
            'event_type' => 'undertaking_requested', 'channel' => 'inapp',
        ]);

        $license = $service->assign($license, 11);
        $this->assertSame(11, (int) $license->responsible_user_id);
        $this->assertSame(2, LicenseUndertaking::query()->where('license_id', $license->id)->count());
        $this->assertDatabaseHas('license_undertakings', [
            'license_id' => $license->id, 'user_id' => 11, 'status' => 'pending',
        ]);

        $this->grant(11, 'licenses.view', 'licenses.process');
        $this->actAsLicenseUser(11);
        $responsibleService = app(LicenseService::class);
        $fresh = $responsibleService->findOrFail($license->id);
        $this->assertTrue($responsibleService->requiresUndertaking($fresh));
        $this->assertThrows(
            fn () => $responsibleService->addComment($fresh, 'Must be blocked'),
            ValidationException::class,
        );

        $accepted = $responsibleService->acceptUndertaking($fresh, '127.0.0.1', 'License test agent');
        $this->assertSame('accepted', $accepted->status);
        $this->assertSame($snapshot, $accepted->undertaking_text);
        $this->assertSame('127.0.0.1', $accepted->ip);
        $this->assertSame('License test agent', $accepted->user_agent);
        $this->assertSame('2026-08-30 09:00:00', $accepted->accepted_at?->format('Y-m-d H:i:s'));
        $this->assertFalse($responsibleService->requiresUndertaking($fresh));

        $comment = $responsibleService->addComment($fresh, 'Renewal work has started');
        $this->assertSame(11, (int) $comment->user_id);
        $this->assertDatabaseHas('license_timeline', [
            'license_id' => $license->id, 'event_type' => 'undertaking_accepted',
        ]);
    }

    public function test_private_attachments_validate_types_and_enforce_parent_path_and_scope_authorization(): void
    {
        $valid = UploadedFile::fake()->create('permit.pdf', 12, 'application/pdf');
        $invalid = UploadedFile::fake()->create('payload.exe', 12, 'application/octet-stream');
        $rules = (new StoreLicenseAttachmentRequest)->rules();
        $this->assertFalse(Validator::make(['file' => $valid], $rules)->fails());
        $this->assertTrue(Validator::make(['file' => $invalid], $rules)->fails());

        $license = $this->makeLicense(ownerId: 10, branchIds: [1], title: 'Attachment parent');
        $differentLicense = $this->makeLicense(ownerId: 10, branchIds: [1], title: 'Different parent');
        $this->actAsLicenseUser(1);
        $service = app(LicenseService::class);
        $attachment = $service->storeAttachment($license, $valid, 'Private copy');

        Storage::disk('local')->assertExists($attachment->file_path);
        Storage::disk('public')->assertMissing($attachment->file_path);
        $this->assertStringStartsWith('licenses/'.$license->id.'/', $attachment->file_path);
        $response = $service->downloadAttachment($license, $attachment->id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));

        try {
            $service->downloadAttachment($differentLicense, $attachment->id);
            $this->fail('An attachment was downloadable through a different parent license.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }

        $traversal = LicenseAttachment::query()->create([
            'license_id' => $license->id,
            'context' => 'license',
            'file_path' => '../outside.pdf',
            'original_name' => 'outside.pdf',
            'mime' => 'application/pdf',
            'size' => 10,
            'uploaded_by' => 1,
            'uploaded_at' => now(),
        ]);
        try {
            $service->downloadAttachment($license, $traversal->id);
            $this->fail('A traversal attachment path was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }

        $this->grant(10, 'licenses.view', 'licenses.process');
        $this->actAsLicenseUser(10);
        $otherBranch = $this->makeLicense(ownerId: 10, branchIds: [2], title: 'Scoped away');
        $this->assertNull(app(LicenseRepository::class)->findForDetail($otherBranch->id));
    }

    public function test_renewal_completion_updates_expiry_and_preserves_each_cycle_history(): void
    {
        CarbonImmutable::setTestNow('2026-09-01 10:00:00');
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
        $this->actAsLicenseUser(10);
        $service = app(LicenseService::class);

        $firstCycle = $service->startRenewal($license, 'First renewal cycle');
        $this->assertSame('2026-12-31', $firstCycle->previous_expiry_date?->format('Y-m-d'));
        $this->assertSame('under_renewal', $license->fresh()->status?->code);

        $copy = UploadedFile::fake()->create('renewed-license.pdf', 24, 'application/pdf');
        $completed = $service->completeRenewal($license->fresh(), '2027-12-31', 'Renewed successfully', $copy);
        $firstCycle->refresh();
        $this->assertSame('2027-12-31', $completed->expiry_date?->format('Y-m-d'));
        $this->assertSame('active', $completed->status?->code);
        $this->assertSame('completed', $completed->renewalStage?->code);
        $this->assertSame('2027-12-31', $firstCycle->new_expiry_date?->format('Y-m-d'));
        $this->assertNotNull($firstCycle->completed_at);
        $this->assertSame(10, (int) $firstCycle->completed_by);
        $this->assertDatabaseHas('license_attachments', [
            'license_id' => $license->id,
            'renewal_id' => $firstCycle->id,
            'context' => 'renewal',
        ]);
        $this->assertDatabaseHas('license_timeline', [
            'license_id' => $license->id, 'event_type' => 'renewal_completed',
        ]);
        $this->assertDatabaseHas('license_notifications', [
            'license_id' => $license->id, 'event_type' => 'renewal_completed', 'channel' => 'inapp',
        ]);

        CarbonImmutable::setTestNow('2027-09-01 10:00:00');
        $secondCycle = $service->startRenewal($completed, 'Second renewal cycle');
        $this->assertSame('2027-12-31', $secondCycle->previous_expiry_date?->format('Y-m-d'));
        $this->assertSame(2, LicenseRenewal::query()->where('license_id', $license->id)->count());
        $this->assertDatabaseHas('license_renewals', [
            'id' => $firstCycle->id, 'license_id' => $license->id, 'new_expiry_date' => '2027-12-31',
        ]);
        $this->assertSame(1, LicenseNotification::query()->where('license_id', $license->id)
            ->where('event_type', 'renewal_completed')->where('channel', 'inapp')->count());
    }

    public function test_license_flash_messages_are_translated_in_arabic_and_english(): void
    {
        $keys = [
            'created', 'updated', 'assigned', 'undertaking_accepted', 'undertaking_rejected',
            'stage_updated', 'comment_added', 'attachment_uploaded', 'external_communication_added',
            'payment_created', 'renewal_started', 'renewal_completed', 'payment_status_updated',
            'documents_requested', 'settings_saved', 'member_added', 'member_removed',
        ];

        foreach (['ar', 'en'] as $locale) {
            foreach ($keys as $key) {
                $translationKey = 'licenses.flash.'.$key;
                $translated = trans($translationKey, [], $locale);

                $this->assertNotSame($translationKey, $translated, $locale.' is missing '.$translationKey);
                $this->assertNotSame('', trim((string) $translated), $locale.' has an empty '.$translationKey);
            }
        }
    }
}

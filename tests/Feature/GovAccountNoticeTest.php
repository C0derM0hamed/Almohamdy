<?php

namespace Tests\Feature;

use App\Mail\GovAccountNoticeMail;
use App\Models\GovAccount;
use App\Models\GovAccountNotice;
use App\Models\GovAccountNoticeRecipient;
use App\Models\GovAccountRequest;
use App\Services\GovAccounts\GovAccountNoticeService;
use Database\Seeders\GovAccountReferenceSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;

class GovAccountNoticeTest extends GovAccountModuleTestCase
{
    private int $authorityId;

    private int $serviceId;

    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GovAccountReferenceSeeder::class);
        $this->authorityId = (int) DB::table('gov_account_authorities')->where('companies_groups_id', 1)->value('id');
        $this->serviceId = (int) DB::table('gov_account_services')->where('companies_groups_id', 1)->where('authority_id', $this->authorityId)->value('id');
        $this->roleId = (int) DB::table('gov_account_roles')->where('companies_groups_id', 1)->value('id');
        DB::table('ra_users')->insert([
            ['hr_id' => 12, 'hr_first_name' => 'Second Employee', 'hr_email_address' => 'user12@example.test', 'hr_username' => 'user12', 'hr_password' => 'unused', 'hr_user_level' => 1, 'branch_id' => 2, 'companies_groups_id' => 1, 'groupid' => 12, 'activated' => true, 'created_at' => now(), 'updated_at' => now()],
            ['hr_id' => 13, 'hr_first_name' => 'Inactive Employee', 'hr_email_address' => 'user13@example.test', 'hr_username' => 'user13', 'hr_password' => 'unused', 'hr_user_level' => 1, 'branch_id' => 1, 'companies_groups_id' => 1, 'groupid' => 13, 'activated' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->grant(10, 'gov_accounts.process');
        config(['hm.gov_accounts.notifications.mail' => true]);
    }

    public function test_processor_can_create_send_and_view_recipient_status_board(): void
    {
        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $this->get(route('modules.gov-accounts.notices.create'))->assertOk()->assertSee('New notice');
        $this->post(route('modules.gov-accounts.notices.store'), $this->payload('users', ['user_ids' => [11, 12]]))->assertRedirect();
        $notice = GovAccountNotice::query()->firstOrFail();
        $this->assertSame(['mode' => 'users', 'ids' => [11, 12]], $notice->targeting);

        $this->post(route('modules.gov-accounts.notices.send', $notice))->assertRedirect();
        $this->assertSame(2, $notice->recipients()->count());
        $this->assertNotNull($notice->fresh()->sent_at);
        $this->assertSame(2, DB::table('gov_account_notifications')->where('notice_id', $notice->id)->where('channel', 'inapp')->count());
        Mail::assertSent(GovAccountNoticeMail::class, 2);
        $indexHtml = $this->get(route('modules.gov-accounts.notices.index'))
            ->assertOk()
            ->assertSee('id="govNoticeQuickViewModal"', false)
            ->assertSee('data-license-preview-open', false)
            ->assertSee('data-bs-target="#govNoticeQuickViewModal"', false)
            ->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/<a class="lic-btn[^"]*" href="[^"]*\/modules\/gov-accounts\/notices\/'.$notice->id.'"/',
            $indexHtml
        );
        $this->get(route('modules.gov-accounts.notices.show', $notice))->assertOk()->assertSee('Not viewed')->assertSee('user11@example.test')->assertSee('lic-summary-grid', false);
    }

    public function test_all_specific_department_and_service_targeting_are_scoped_and_unique(): void
    {
        $this->makeAccount(11, 1, $this->serviceId);
        $this->makeAccount(12, 2, $this->serviceId);
        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $service = app(GovAccountNoticeService::class);

        $all = $this->notice('all');
        $this->assertEqualsCanonicalizing([1, 10, 11, 12], $service->resolveRecipients($all)->pluck('hr_id')->all());
        $specific = $this->notice('users', [12]);
        $this->assertSame([12], $service->resolveRecipients($specific)->pluck('hr_id')->all());
        $department = $this->notice('departments', [1]);
        $this->assertSame([11], $service->resolveRecipients($department)->pluck('hr_id')->all());
        $serviceNotice = $this->notice('service', [], $this->serviceId);
        $this->assertEqualsCanonicalizing([11, 12], $service->resolveRecipients($serviceNotice)->pluck('hr_id')->all());

        $service->send($serviceNotice);
        $tokens = GovAccountNoticeRecipient::query()->where('notice_id', $serviceNotice->id)->pluck('token');
        $this->assertCount(2, $tokens);
        $this->assertCount(2, $tokens->unique());
        $tokens->each(fn (string $token) => $this->assertSame(64, strlen($token)));
        $this->assertDatabaseMissing('gov_account_notice_recipients', ['user_id' => 40]);
    }

    public function test_public_token_records_first_and_duplicate_views_without_disclosing_recipients(): void
    {
        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $notice = $this->notice('users', [11, 12]);
        app(GovAccountNoticeService::class)->send($notice);
        $recipient = GovAccountNoticeRecipient::query()->where('notice_id', $notice->id)->where('user_id', 11)->firstOrFail();

        $this->get(route('public.gov-account-notices.view', $recipient->token))
            ->assertOk()->assertSee($notice->title)->assertSee('Open meeting link')->assertDontSee('user12@example.test');
        $firstViewedAt = $recipient->fresh()->viewed_at;
        $this->assertNotNull($firstViewedAt);
        $this->assertSame(1, $recipient->fresh()->view_count);

        $this->travel(2)->minutes();
        $this->get(route('public.gov-account-notices.view', $recipient->token))->assertOk();
        $recipient->refresh();
        $this->assertTrue($firstViewedAt->equalTo($recipient->viewed_at));
        $this->assertSame(2, $recipient->view_count);
        $this->assertTrue($recipient->last_viewed_at->greaterThan($firstViewedAt));
        $this->get('/gov-account-notices/view/'.str_repeat('f', 64))->assertNotFound();
        $this->get('/gov-account-notices/view/short')->assertNotFound();
    }

    public function test_permissions_and_company_scope_are_enforced_on_direct_routes_and_payloads(): void
    {
        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.notices.index'))->assertForbidden();

        $other = GovAccountNotice::query()->create($this->noticeAttributes('all') + ['companies_groups_id' => 2, 'targeting' => ['mode' => 'all', 'ids' => []], 'created_by' => 40, 'publish' => true]);
        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $this->get(route('modules.gov-accounts.notices.show', $other))->assertNotFound();
        $this->post(route('modules.gov-accounts.notices.store'), $this->payload('users', ['user_ids' => [40]]))->assertSessionHasErrors('user_ids');
    }

    public function test_notice_form_supports_the_deployed_legacy_department_table_shape(): void
    {
        Schema::drop('branches_departments');
        Schema::create('branches_departments', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->string('name_en');
        });
        DB::table('branches_departments')->insert(['id' => 1, 'name_ar' => 'الطبية', 'name_en' => 'Medical']);
        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $this->get(route('modules.gov-accounts.notices.create'))->assertOk()->assertSee('Medical');
        $this->post(route('modules.gov-accounts.notices.store'), $this->payload('departments', ['department_ids' => [1]]))->assertRedirect();
        $this->assertDatabaseHas('gov_account_notices', ['companies_groups_id' => 1]);
    }

    public function test_notice_attachments_are_validated_audited_and_securely_downloaded(): void
    {
        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $payload = $this->payload('users', ['user_ids' => [11], 'attachments' => [UploadedFile::fake()->create('agenda.pdf', 20, 'application/pdf')]]);
        $this->post(route('modules.gov-accounts.notices.store'), $payload)->assertRedirect();
        $notice = GovAccountNotice::query()->firstOrFail();
        $attachment = $notice->attachments()->firstOrFail();
        $this->assertDatabaseHas('gov_account_timeline', ['notice_id' => $notice->id, 'event_type' => 'notice_attachment_uploaded']);
        $this->get(route('modules.gov-accounts.notices.show', $notice))->assertOk()->assertSee('agenda.pdf');
        $this->get(route('modules.gov-accounts.notices.attachments.download', [$notice, $attachment]))->assertOk();

        $this->get(route('modules.gov-accounts.notices.edit', $notice))->assertOk()->assertSee($notice->title);
        $updated = array_replace($this->payload('users', ['user_ids' => [11]]), ['title' => 'Updated compliance training', 'attachments' => [UploadedFile::fake()->create('updated-agenda.png', 10, 'image/png')]]);
        $this->put(route('modules.gov-accounts.notices.update', $notice), $updated)->assertRedirect(route('modules.gov-accounts.notices.show', $notice));
        $this->assertDatabaseHas('gov_account_notices', ['id' => $notice->id, 'title' => 'Updated compliance training']);
        $this->assertDatabaseHas('gov_account_attachments', ['notice_id' => $notice->id, 'original_name' => 'updated-agenda.png']);
        $this->assertDatabaseHas('gov_account_timeline', ['notice_id' => $notice->id, 'event_type' => 'notice_updated']);

        $this->post(route('modules.gov-accounts.notices.attachments.store', $notice), ['attachment' => UploadedFile::fake()->create('unsafe.exe', 2)])
            ->assertSessionHasErrors('attachment');
        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.notices.attachments.download', [$notice, $attachment]))->assertForbidden();
    }

    private function payload(string $mode, array $extra = []): array
    {
        return $this->noticeAttributes($mode) + $extra + ['targeting_mode' => $mode];
    }

    private function notice(string $mode, array $ids = [], ?int $serviceId = null): GovAccountNotice
    {
        return GovAccountNotice::query()->create($this->noticeAttributes($mode, $serviceId) + [
            'companies_groups_id' => 1,
            'targeting' => ['mode' => $mode, 'ids' => $ids],
            'created_by' => 10,
            'publish' => true,
        ]);
    }

    private function noticeAttributes(string $mode, ?int $serviceId = null): array
    {
        return [
            'title' => 'Official compliance training',
            'authority_id' => $this->authorityId,
            'service_id' => $serviceId ?? ($mode === 'service' ? $this->serviceId : null),
            'description' => 'Required training for official account holders.',
            'event_date' => '2026-09-15',
            'event_time' => '10:30',
            'meeting_url' => 'https://meet.example.test/session',
            'attendance_method' => 'online',
            'location' => null,
            'notes' => 'Join ten minutes early.',
        ];
    }

    private function makeAccount(int $employeeId, int $departmentId, int $serviceId): GovAccount
    {
        $request = GovAccountRequest::query()->create(['companies_groups_id' => 1, 'branch_id' => $departmentId === 1 ? 1 : 2, 'type' => 'create', 'status' => 'completed', 'origin' => 'department', 'employee_user_id' => $employeeId, 'department_id' => $departmentId, 'authority_id' => $this->authorityId, 'service_id' => $serviceId, 'role_id' => $this->roleId, 'justification' => 'Existing account', 'round' => 1, 'created_by' => 10]);

        return GovAccount::query()->create(['companies_groups_id' => 1, 'branch_id' => $departmentId === 1 ? 1 : 2, 'employee_user_id' => $employeeId, 'authority_id' => $this->authorityId, 'service_id' => $serviceId, 'role_id' => $this->roleId, 'username' => 'ACC-'.$employeeId, 'status' => 'active', 'created_from_request_id' => $request->id, 'account_created_at' => '2026-08-01']);
    }
}

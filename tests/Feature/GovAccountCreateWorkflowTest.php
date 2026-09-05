<?php

namespace Tests\Feature;

use App\Models\GovAccountRequest;
use Database\Seeders\GovAccountReferenceSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GovAccountCreateWorkflowTest extends GovAccountModuleTestCase
{
    protected int $authorityId;

    protected int $serviceId;

    protected int $roleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GovAccountReferenceSeeder::class);
        $this->authorityId = (int) DB::table('gov_account_authorities')->where('companies_groups_id', 1)->value('id');
        $this->serviceId = (int) DB::table('gov_account_services')->where('companies_groups_id', 1)->where('authority_id', $this->authorityId)->value('id');
        $this->roleId = (int) DB::table('gov_account_roles')->where('companies_groups_id', 1)->value('id');
        DB::table('gov_account_department_heads')->insert(['companies_groups_id' => 1, 'department_id' => 1, 'user_id' => 10, 'publish' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->grant(10, 'gov_accounts.request');
        $this->grant(1, 'gov_accounts.process', 'gov_accounts.view');
    }

    public function test_complete_create_workflow_preserves_undertakings_rejection_rounds_timeline_and_account(): void
    {
        $request = $this->createDraft();
        $this->post(route('modules.gov-accounts.requests.submit', $request), ['manager_undertaking' => 1])->assertRedirect();
        $this->assertDatabaseHas('gov_account_undertakings', ['request_id' => $request->id, 'kind' => 'manager', 'user_id' => 10, 'status' => 'accepted']);
        $this->assertDatabaseHas('gov_account_undertakings', ['request_id' => $request->id, 'kind' => 'employee', 'user_id' => 11, 'status' => 'pending']);

        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.undertakings.show', $request))->assertOk()->assertSee(__('gov_accounts.undertakings.employee_text'));
        $this->post(route('modules.gov-accounts.undertakings.accept', $request), ['employee_undertaking' => 1])->assertRedirect();
        $this->assertDatabaseHas('gov_account_requests', ['id' => $request->id, 'status' => 'under_review']);

        $this->actAsGovAccountUser(1);
        $this->get(route('modules.gov-accounts.requests.show', $request))->assertOk()->assertSee('Operational government service access');
        $this->post(route('modules.gov-accounts.requests.reject', $request), ['reason' => 'Please clarify the operational need.'])->assertRedirect();
        $this->assertDatabaseHas('gov_account_requests', ['id' => $request->id, 'status' => 'rejected', 'rejection_reason' => 'Please clarify the operational need.']);

        $this->actAsGovAccountUser(10);
        $this->put(route('modules.gov-accounts.requests.update', $request), $this->requestPayload(['justification' => 'Expanded operational reason']))->assertRedirect();
        $this->post(route('modules.gov-accounts.requests.resubmit', $request), ['response' => 'The operational requirement is now clarified.'])->assertRedirect();
        $this->assertDatabaseHas('gov_account_requests', ['id' => $request->id, 'status' => 'under_review', 'round' => 2, 'rejection_reason' => null]);

        $this->actAsGovAccountUser(1);
        $this->post(route('modules.gov-accounts.requests.approve', $request), ['notes' => 'Approved internally'])->assertRedirect();
        $this->post(route('modules.gov-accounts.requests.authority', $request), ['authority_submitted_at' => '2026-08-31', 'authority_reference' => 'AUTH-100'])->assertRedirect();
        $this->post(route('modules.gov-accounts.requests.complete', $request), ['username' => 'EMP11-MOH', 'login_url' => 'https://example.gov/login', 'reference_no' => 'AUTH-100', 'role_id' => $this->roleId, 'account_created_at' => '2026-08-31'])->assertRedirect();

        $this->assertDatabaseHas('gov_accounts', ['employee_user_id' => 11, 'authority_id' => $this->authorityId, 'username' => 'EMP11-MOH', 'status' => 'active']);
        $this->assertDatabaseCount('gov_accounts', 1);
        $this->assertDatabaseHas('gov_account_timeline', ['request_id' => $request->id, 'event_type' => 'resubmitted']);
        $this->assertDatabaseHas('gov_account_timeline', ['request_id' => $request->id, 'event_type' => 'completed']);
        $this->assertDatabaseHas('gov_account_notifications', ['request_id' => $request->id, 'recipient_user_id' => 11, 'event_type' => 'account_created', 'channel' => 'inapp']);
        $accountId = (int) DB::table('gov_accounts')->where('username', 'EMP11-MOH')->value('id');
        $this->get(route('modules.gov-accounts.accounts.show', $accountId))->assertOk()->assertSee('EMP11-MOH');
        $employeeNotificationId = (int) DB::table('gov_account_notifications')->where('recipient_user_id', 11)->where('channel', 'inapp')->latest('id')->value('id');
        $this->post(route('modules.gov-accounts.notifications.read', $employeeNotificationId))->assertNotFound();
        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.notifications.index'))->assertOk();
        $this->post(route('modules.gov-accounts.notifications.read', $employeeNotificationId))->assertRedirect();
        $this->assertDatabaseMissing('gov_account_notifications', ['id' => $employeeNotificationId, 'read_at' => null]);
    }

    public function test_employee_can_have_multiple_accounts_in_the_same_authority(): void
    {
        $first = $this->completeStraightThrough('EMP11-A');
        $second = $this->completeStraightThrough('EMP11-B');

        $this->assertNotSame($first, $second);
        $this->assertSame(2, DB::table('gov_accounts')->where('employee_user_id', 11)->where('authority_id', $this->authorityId)->count());
    }

    public function test_head_and_processor_can_cancel_allowed_request_states(): void
    {
        $draft = $this->createDraft();
        $this->get(route('modules.gov-accounts.requests.show', $draft))
            ->assertOk()
            ->assertSee('action="'.route('modules.gov-accounts.requests.cancel', $draft).'"', false);
        $this->post(route('modules.gov-accounts.requests.cancel', $draft))->assertRedirect();
        $this->assertDatabaseHas('gov_account_requests', ['id' => $draft->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('gov_account_timeline', ['request_id' => $draft->id, 'event_type' => 'cancelled']);

        $waiting = $this->createDraft();
        $this->post(route('modules.gov-accounts.requests.submit', $waiting), ['manager_undertaking' => 1])->assertRedirect();
        $this->post(route('modules.gov-accounts.requests.cancel', $waiting))->assertRedirect();
        $this->assertDatabaseHas('gov_account_requests', ['id' => $waiting->id, 'status' => 'cancelled']);

        $review = $this->createDraft();
        $this->post(route('modules.gov-accounts.requests.submit', $review), ['manager_undertaking' => 1]);
        $this->actAsGovAccountUser(11);
        $this->post(route('modules.gov-accounts.undertakings.accept', $review), ['employee_undertaking' => 1]);
        $this->actAsGovAccountUser(10);
        $this->post(route('modules.gov-accounts.requests.cancel', $review))->assertForbidden();
        $this->actAsGovAccountUser(1);
        $this->get(route('modules.gov-accounts.requests.show', $review))
            ->assertOk()
            ->assertSee('action="'.route('modules.gov-accounts.requests.cancel', $review).'"', false);
        $this->post(route('modules.gov-accounts.requests.cancel', $review))->assertRedirect();
        $this->assertDatabaseHas('gov_account_requests', ['id' => $review->id, 'status' => 'cancelled']);
    }

    public function test_scope_transition_and_attachment_guards_block_idor_and_invalid_actions(): void
    {
        $request = $this->createDraft();
        $this->actAsGovAccountUser(1);
        $this->post(route('modules.gov-accounts.requests.approve', $request))->assertSessionHasErrors('status');

        $this->actAsGovAccountUser(10);
        $file = UploadedFile::fake()->create('evidence.pdf', 20, 'application/pdf');
        $this->post(route('modules.gov-accounts.requests.attachments.store', $request), ['attachment' => $file, 'context' => 'request'])->assertRedirect();
        $attachment = DB::table('gov_account_attachments')->where('request_id', $request->id)->first();
        Storage::disk('local')->assertExists($attachment->file_path);
        $this->get(route('modules.gov-accounts.requests.attachments.download', [$request, $attachment->id]))->assertOk();

        DB::table('gov_account_department_heads')->insert(['companies_groups_id' => 2, 'department_id' => 3, 'user_id' => 40, 'publish' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->grant(40, 'gov_accounts.request');
        $this->actAsGovAccountUser(40);
        $this->get(route('modules.gov-accounts.requests.show', $request))->assertNotFound();
        $this->get(route('modules.gov-accounts.requests.attachments.download', [$request, $attachment->id]))->assertNotFound();

        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.requests.create'))->assertForbidden();
    }

    private function createDraft(): GovAccountRequest
    {
        $this->actAsGovAccountUser(10);
        $this->post(route('modules.gov-accounts.requests.store'), $this->requestPayload())->assertSessionHasNoErrors()->assertRedirect();

        return GovAccountRequest::query()->latest('id')->firstOrFail();
    }

    private function completeStraightThrough(string $username): int
    {
        $request = $this->createDraft();
        $this->post(route('modules.gov-accounts.requests.submit', $request), ['manager_undertaking' => 1]);
        $this->actAsGovAccountUser(11);
        $this->post(route('modules.gov-accounts.undertakings.accept', $request), ['employee_undertaking' => 1]);
        $this->actAsGovAccountUser(1);
        $this->post(route('modules.gov-accounts.requests.approve', $request));
        $this->post(route('modules.gov-accounts.requests.authority', $request), ['authority_submitted_at' => '2026-08-31']);
        $this->post(route('modules.gov-accounts.requests.complete', $request), ['username' => $username, 'login_url' => 'https://example.gov/login', 'role_id' => $this->roleId, 'account_created_at' => '2026-08-31'])->assertRedirect();

        return (int) DB::table('gov_accounts')->where('username', $username)->value('id');
    }

    private function requestPayload(array $overrides = []): array
    {
        return $overrides + ['employee_user_id' => 11, 'department_id' => 1, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'justification' => 'Operational government service access', 'notes' => 'No credentials stored'];
    }
}

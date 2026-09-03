<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds a small, repeatable Government Accounts review dataset.
 *
 * This is intentionally separate from the reference seeder so a developer
 * can opt into business/demo records with:
 *
 *   php artisan db:seed --class=GovAccountDemoSeeder
 *
 * Records are keyed by a visible demo marker and are updated on subsequent
 * runs rather than duplicated. It only uses existing users/departments in
 * the first active company and never creates login accounts or passwords.
 */
class GovAccountDemoSeeder extends Seeder
{
    private const MARKER = '[GOV-ACCOUNTS-DEMO]';

    public function run(): void
    {
        if (! Schema::hasTable('gov_account_requests') || ! Schema::hasTable('gov_accounts') || ! Schema::hasTable('gov_account_notices')) {
            $this->command?->warn('Skipped GovAccountDemoSeeder: run the Government Accounts migrations first.');

            return;
        }

        $this->call(GovAccountReferenceSeeder::class);

        $companyId = $this->companyId();
        $users = $this->users($companyId);
        $departments = $this->departments($users);
        $authority = DB::table('gov_account_authorities')->where('companies_groups_id', $companyId)->where('publish', true)->orderBy('ranking')->first();
        $service = $authority ? DB::table('gov_account_services')->where('companies_groups_id', $companyId)->where('authority_id', $authority->id)->where('publish', true)->orderBy('ranking')->first() : null;
        $role = DB::table('gov_account_roles')->where('companies_groups_id', $companyId)->where('publish', true)->orderBy('ranking')->first();

        if ($users === [] || $departments === [] || $authority === null || $service === null || $role === null) {
            $this->command?->warn('Skipped GovAccountDemoSeeder: no usable company users, departments, or references were found.');

            return;
        }

        $headId = $users[0]['id'];
        $employeeId = $users[1]['id'] ?? $headId;
        $processorId = $users[2]['id'] ?? $headId;
        $departmentId = $departments[0]['id'];
        $branchId = $departments[0]['branch_id'] ?: ($users[0]['branch_id'] ?: 1);

        $this->seedDepartmentHead($companyId, $departmentId, $headId);

        $completed = [];
        foreach ([
            ['key' => 'active', 'employee' => $employeeId, 'status' => 'active', 'username' => 'DEMO-MOH-REPORTS'],
            ['key' => 'active-secondary', 'employee' => $employeeId, 'status' => 'active', 'username' => 'DEMO-MOH-REPORTS-SECONDARY'],
            ['key' => 'active-tertiary', 'employee' => $employeeId, 'status' => 'active', 'username' => 'DEMO-MOH-REPORTS-TERTIARY'],
            ['key' => 'active-quaternary', 'employee' => $employeeId, 'status' => 'active', 'username' => 'DEMO-MOH-REPORTS-QUATERNARY'],
            ['key' => 'suspended', 'employee' => $employeeId, 'status' => 'suspended', 'username' => 'DEMO-MOH-LEAVE'],
            ['key' => 'closed', 'employee' => $users[3]['id'] ?? $employeeId, 'status' => 'closed', 'username' => 'DEMO-MOH-LICENSE'],
        ] as $fixture) {
            $request = $this->upsertRequest($companyId, $branchId, $fixture['key'], 'create', 'completed', $fixture['employee'], $departmentId, $authority->id, $service->id, $role->id, $headId, [
                'authority_reference' => 'DEMO-'.$fixture['key'],
            ]);
            $account = $this->upsertAccount($companyId, $branchId, $fixture['key'], $request->id, $fixture['employee'], $authority->id, $service->id, $role->id, $fixture['username'], $fixture['status'], $processorId);
            DB::table('gov_account_requests')->where('id', $request->id)->update(['account_id' => $account->id]);
            $completed[$fixture['key']] = $account;
        }

        foreach ([
            ['key' => 'awaiting-employee', 'status' => 'awaiting_employee', 'employee' => $employeeId],
            ['key' => 'under-review', 'status' => 'under_review', 'employee' => $users[3]['id'] ?? $employeeId],
            ['key' => 'rejected', 'status' => 'rejected', 'employee' => $employeeId],
            ['key' => 'approved', 'status' => 'approved', 'employee' => $employeeId],
            ['key' => 'authority-follow-up', 'status' => 'submitted_to_authority', 'employee' => $employeeId],
        ] as $fixture) {
            $request = $this->upsertRequest($companyId, $branchId, $fixture['key'], 'create', $fixture['status'], $fixture['employee'], $departmentId, $authority->id, $service->id, $role->id, $headId, [
                'rejection_reason' => $fixture['status'] === 'rejected' ? 'Please attach the department approval before resubmitting.' : null,
                'authority_reference' => $fixture['status'] === 'submitted_to_authority' ? 'DEMO-AUTH-2026-001' : null,
            ]);
            $this->seedUndertakings($request->id, $fixture['employee'], $headId, $fixture['status'] === 'awaiting_employee');
        }

        foreach ([
            ['key' => 'modification', 'type' => 'modify', 'status' => 'under_review', 'account' => 'active'],
            ['key' => 'permission-change', 'type' => 'permission_change', 'status' => 'approved', 'account' => 'active-secondary'],
            ['key' => 'suspension', 'type' => 'suspend', 'status' => 'under_review', 'account' => 'active-tertiary'],
            ['key' => 'closure', 'type' => 'close', 'status' => 'under_review', 'account' => 'active-quaternary'],
        ] as $fixture) {
            $account = $completed[$fixture['account']];
            $request = $this->upsertRequest($companyId, $branchId, $fixture['key'], $fixture['type'], $fixture['status'], $account->employee_user_id, $departmentId, $authority->id, $service->id, $role->id, $headId, [
                'account_id' => $account->id,
                'requested_role_id' => $fixture['type'] === 'permission_change' ? $role->id : null,
            ]);
            $inFlight = ['modify' => 'modification_requested', 'permission_change' => 'modification_requested', 'suspend' => 'suspension_requested', 'close' => 'closure_requested'][$fixture['type']];
            if ($fixture['status'] === 'under_review' || $fixture['status'] === 'approved') {
                DB::table('gov_accounts')->where('id', $account->id)->update(['status' => $inFlight]);
            }
            DB::table('gov_account_requests')->where('id', $request->id)->update(['meta' => json_encode(['previous_account_status' => 'active', 'demo_seed_key' => $fixture['key']])]);
        }

        $this->seedNotices($companyId, $authority->id, $service->id, $users, $processorId);
        $this->seedNotifications($companyId, $employeeId, $processorId, $users);
        $this->command?->info('Government Accounts demo data is ready (references, requests, accounts, lifecycle cases, and notices).');
    }

    private function companyId(): int
    {
        return (int) (DB::table('ra_users')->where('activated', '1')->orderBy('hr_id')->value('companies_groups_id') ?: 1);
    }

    /** @return list<array{id:int,branch_id:int,email:string}> */
    private function users(int $companyId): array
    {
        return DB::table('ra_users')->where('companies_groups_id', $companyId)->where('activated', '1')->whereNotNull('hr_email_address')->orderBy('hr_id')->get(['hr_id as id', 'branch_id', 'hr_email_address as email'])->map(fn ($user): array => ['id' => (int) $user->id, 'branch_id' => (int) $user->branch_id, 'email' => (string) $user->email])->values()->all();
    }

    /** @return list<array{id:int,branch_id:int}> */
    private function departments(array $users): array
    {
        if (! Schema::hasTable('branches_departments')) {
            return [];
        }
        $query = DB::table('branches_departments');
        if (Schema::hasColumn('branches_departments', 'publish')) {
            $query->where('publish', true);
        }
        if (Schema::hasColumn('branches_departments', 'branch_id')) {
            $query->whereIn('branch_id', collect($users)->pluck('branch_id')->filter()->unique()->values());
        }

        $columns = ['id'];
        if (Schema::hasColumn('branches_departments', 'branch_id')) {
            $columns[] = 'branch_id';
        }

        $departments = $query->orderBy('id')->limit(3)->get($columns);

        // Some lightweight/local databases do not ship any departments. Add
        // one clearly labelled fixture so the workflow can still be exercised
        // end-to-end; existing department records are always preferred.
        $fallback = ['name_ar' => 'قسم الحسابات الحكومية - بيانات تجريبية'];
        if (Schema::hasColumn('branches_departments', 'publish')) {
            $fallback['publish'] = true;
        }
        if (Schema::hasColumn('branches_departments', 'branch_id')) {
            $fallback['branch_id'] = (int) (collect($users)->pluck('branch_id')->filter()->first() ?: 1);
        }
        if ($departments->isEmpty()) {
            DB::table('branches_departments')->updateOrInsert(
                ['name_en' => 'Government Accounts Demo Department'],
                $fallback,
            );
            $departments = DB::table('branches_departments')->where('name_en', 'Government Accounts Demo Department')->get($columns);
        }

        return $departments->map(fn ($department): array => ['id' => (int) $department->id, 'branch_id' => (int) ($department->branch_id ?? 0)])->all();
    }

    private function seedDepartmentHead(int $companyId, int $departmentId, int $userId): void
    {
        DB::table('gov_account_department_heads')->updateOrInsert(['companies_groups_id' => $companyId, 'department_id' => $departmentId, 'user_id' => $userId], ['publish' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function upsertRequest(int $companyId, int $branchId, string $key, string $type, string $status, int $employeeId, int $departmentId, int $authorityId, int $serviceId, int $roleId, int $createdBy, array $extra = []): object
    {
        $marker = self::MARKER.' '.$key;
        $attributes = ['companies_groups_id' => $companyId, 'branch_id' => $branchId, 'type' => $type, 'status' => $status, 'origin' => 'department', 'employee_user_id' => $employeeId, 'department_id' => $departmentId, 'authority_id' => $authorityId, 'service_id' => $serviceId, 'role_id' => $roleId, 'requested_role_id' => $extra['requested_role_id'] ?? null, 'account_id' => $extra['account_id'] ?? null, 'justification' => $marker.' Request for testing the official accounts workflow.', 'notes' => $marker, 'round' => $status === 'rejected' ? 2 : 1, 'rejection_reason' => $extra['rejection_reason'] ?? null, 'reviewed_by' => in_array($status, ['under_review', 'rejected', 'approved', 'submitted_to_authority', 'completed'], true) ? $createdBy : null, 'reviewed_at' => in_array($status, ['rejected', 'approved', 'completed'], true) ? now() : null, 'authority_submitted_at' => $status === 'submitted_to_authority' ? now() : null, 'authority_submitted_by' => $status === 'submitted_to_authority' ? $createdBy : null, 'authority_reference' => $extra['authority_reference'] ?? null, 'created_by' => $createdBy, 'meta' => json_encode(['demo_seed_key' => $key, 'previous_account_status' => 'active']), 'created_at' => now(), 'updated_at' => now()];
        $id = DB::table('gov_account_requests')->where('companies_groups_id', $companyId)->where('notes', $marker)->value('id');
        if ($id) {
            DB::table('gov_account_requests')->where('id', $id)->update($attributes);
        } else {
            $id = DB::table('gov_account_requests')->insertGetId($attributes);
        }
        $this->seedTimeline((int) $id, $branchId, $marker, $type === 'create' ? 'created' : 'lifecycle_requested');

        return (object) array_merge($attributes, ['id' => (int) $id]);
    }

    private function upsertAccount(int $companyId, int $branchId, string $key, int $requestId, int $employeeId, int $authorityId, int $serviceId, int $roleId, string $username, string $status, int $managedBy): object
    {
        $marker = self::MARKER.' '.$key;
        $attributes = ['companies_groups_id' => $companyId, 'branch_id' => $branchId, 'employee_user_id' => $employeeId, 'authority_id' => $authorityId, 'service_id' => $serviceId, 'role_id' => $roleId, 'username' => $username, 'login_url' => 'https://demo.example.gov.sa/login', 'reference_no' => 'DEMO-'.$key, 'status' => $status, 'created_from_request_id' => $requestId, 'managed_by' => $managedBy, 'account_created_at' => now()->toDateString(), 'suspended_at' => $status === 'suspended' ? now() : null, 'closed_at' => $status === 'closed' ? now() : null, 'closed_reason' => $status === 'closed' ? 'Demo closed account for workflow testing.' : null, 'notes' => $marker, 'created_at' => now(), 'updated_at' => now()];
        $id = DB::table('gov_accounts')->where('companies_groups_id', $companyId)->where('notes', $marker)->value('id');
        if ($id) {
            DB::table('gov_accounts')->where('id', $id)->update($attributes);
        } else {
            $id = DB::table('gov_accounts')->insertGetId($attributes);
        }
        $this->seedTimeline(null, $branchId, $marker, 'completed', (int) $id);

        return (object) array_merge($attributes, ['id' => (int) $id]);
    }

    private function seedUndertakings(int $requestId, int $employeeId, int $headId, bool $employeePending): void
    {
        if (! Schema::hasTable('gov_account_undertakings')) {
            return;
        }

        $now = now();
        DB::table('gov_account_undertakings')->updateOrInsert(['request_id' => $requestId, 'kind' => 'manager'], ['user_id' => $headId, 'undertaking_text' => 'Demo manager undertaking: training and responsibility confirmed.', 'status' => 'accepted', 'requested_at' => $now, 'accepted_at' => $now, 'ip' => '127.0.0.1', 'user_agent' => 'GovAccountDemoSeeder', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('gov_account_undertakings')->updateOrInsert(['request_id' => $requestId, 'kind' => 'employee'], ['user_id' => $employeeId, 'undertaking_text' => 'Demo employee undertaking: confidentiality and work use confirmed.', 'status' => $employeePending ? 'pending' : 'accepted', 'requested_at' => $now, 'accepted_at' => $employeePending ? null : $now, 'ip' => $employeePending ? null : '127.0.0.1', 'user_agent' => $employeePending ? null : 'GovAccountDemoSeeder', 'created_at' => $now, 'updated_at' => $now]);
    }

    private function seedNotices(int $companyId, int $authorityId, int $serviceId, array $users, int $createdBy): void
    {
        $now = now();
        $first = $users[0];
        foreach ([['key' => 'draft', 'title' => 'Demo — Official accounts training draft', 'sent' => null], ['key' => 'sent', 'title' => 'Demo — Government platform briefing', 'sent' => $now]] as $fixture) {
            $marker = self::MARKER.' notice-'.$fixture['key'];
            $notice = DB::table('gov_account_notices')->where('companies_groups_id', $companyId)->where('notes', $marker)->first();
            $attributes = ['companies_groups_id' => $companyId, 'title' => $fixture['title'], 'authority_id' => $authorityId, 'service_id' => $serviceId, 'description' => 'Demo invitation used to test notice delivery and view tracking.', 'event_date' => now()->addDays(14)->toDateString(), 'event_time' => '10:30:00', 'meeting_url' => 'https://demo.example.gov.sa/meeting', 'attendance_method' => 'online', 'location' => null, 'notes' => $marker, 'targeting' => json_encode(['mode' => 'users', 'ids' => [$first['id']]]), 'created_by' => $createdBy, 'sent_at' => $fixture['sent'], 'publish' => true, 'created_at' => $now, 'updated_at' => $now];
            $noticeId = $notice?->id;
            if ($noticeId) {
                DB::table('gov_account_notices')->where('id', $noticeId)->update($attributes);
            } else {
                $noticeId = DB::table('gov_account_notices')->insertGetId($attributes);
            }
            if ($fixture['sent'] && Schema::hasTable('gov_account_notice_recipients')) {
                $token = hash('sha256', 'gov-account-demo-'.$noticeId.'-'.$first['id']);
                DB::table('gov_account_notice_recipients')->updateOrInsert(['notice_id' => $noticeId, 'user_id' => $first['id']], ['email' => $first['email'], 'token' => $token, 'sent_at' => $fixture['sent'], 'viewed_at' => $now, 'view_count' => 1, 'last_viewed_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            }

            if (Schema::hasTable('gov_account_timeline')) {
                $this->seedNoticeTimeline((int) $noticeId, $marker, 'notice_created');
                if ($fixture['sent']) {
                    $this->seedNoticeTimeline((int) $noticeId, $marker, 'notice_sent');
                }
            }

            // Put a small set of real, private fixture files on the sent
            // notice so its detail/download UI can be reviewed immediately.
            // Upload validation is still exercised by the normal create/edit
            // forms; these files are never public and are safe to overwrite.
            if ($fixture['sent']) {
                $this->seedNoticeAttachments((int) $noticeId, $createdBy, $marker);
            }
        }
    }

    private function seedNoticeAttachments(int $noticeId, int $uploadedBy, string $marker): void
    {
        if (! Schema::hasTable('gov_account_attachments')) {
            return;
        }

        $fixtures = [
            ['name' => 'demo-training-agenda.pdf', 'mime' => 'application/pdf', 'contents' => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n", 'description' => 'Demo PDF agenda'],
            ['name' => 'demo-training-poster.png', 'mime' => 'image/png', 'contents' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='), 'description' => 'Demo image poster'],
            ['name' => 'demo-training-roster.xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'contents' => $this->xlsxFixture(), 'description' => 'Demo Excel attendee roster'],
        ];

        foreach ($fixtures as $fixture) {
            $path = 'private/gov-accounts/demo/notices/'.$noticeId.'/'.$fixture['name'];
            Storage::disk('local')->put($path, $fixture['contents']);
            $attributes = ['notice_id' => $noticeId, 'account_id' => null, 'request_id' => null, 'context' => 'notice', 'file_path' => $path, 'original_name' => $fixture['name'], 'mime' => $fixture['mime'], 'size' => strlen((string) $fixture['contents']), 'description' => $fixture['description'], 'uploaded_by' => $uploadedBy, 'uploaded_at' => now()];
            $query = DB::table('gov_account_attachments')->where('notice_id', $noticeId)->where('original_name', $fixture['name']);
            if ($query->exists()) {
                $query->update($attributes);
            } else {
                DB::table('gov_account_attachments')->insert($attributes);
            }
        }

        if (Schema::hasTable('gov_account_timeline')) {
            $this->seedNoticeTimeline($noticeId, $marker, 'notice_attachment_uploaded');
        }
    }

    private function xlsxFixture(): string
    {
        if (! class_exists(\ZipArchive::class)) {
            return "Employee,Attendance\nDemo Employee,Confirmed\n";
        }

        $temporary = tempnam(sys_get_temp_dir(), 'gov-account-demo-xlsx-');
        if ($temporary === false) {
            return "Employee,Attendance\nDemo Employee,Confirmed\n";
        }

        $zip = new \ZipArchive();
        $zip->open($temporary, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Roster" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Employee</t></is></c><c r="B1" t="inlineStr"><is><t>Attendance</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>Demo Employee</t></is></c><c r="B2" t="inlineStr"><is><t>Confirmed</t></is></c></row></sheetData></worksheet>');
        $zip->close();
        $contents = file_get_contents($temporary) ?: '';
        @unlink($temporary);

        return $contents;
    }

    private function seedNotifications(int $companyId, int $employeeId, int $processorId, array $users): void
    {
        if (! Schema::hasTable('gov_account_notifications')) {
            return;
        }

        $emails = collect($users)->keyBy('id');
        $fixtures = [
            ['request' => 'awaiting-employee', 'recipient' => $employeeId, 'event' => 'employee_undertaking_requested', 'status' => 'action_required', 'read_at' => null],
            ['request' => 'approved', 'recipient' => $users[0]['id'], 'event' => 'approved', 'status' => 'logged', 'read_at' => now()],
            ['request' => 'rejected', 'recipient' => $employeeId, 'event' => 'rejected', 'status' => 'logged', 'read_at' => now()],
            ['request' => 'authority-follow-up', 'recipient' => $processorId, 'event' => 'submitted_to_authority', 'status' => 'logged', 'read_at' => null],
            ['request' => 'modification', 'recipient' => $processorId, 'event' => 'lifecycle_requested', 'status' => 'action_required', 'read_at' => null],
        ];

        foreach ($fixtures as $fixture) {
            $request = DB::table('gov_account_requests')->where('companies_groups_id', $companyId)->where('notes', self::MARKER.' '.$fixture['request'])->first();
            $recipient = $emails->get($fixture['recipient']);
            if (! $request || ! $recipient) {
                continue;
            }

            $seedKey = 'notification-'.$fixture['request'];
            $attributes = [
                'request_id' => $request->id,
                'account_id' => $request->account_id,
                'event_type' => $fixture['event'],
                'recipient_user_id' => $fixture['recipient'],
                'recipient_email' => $recipient['email'] ?: null,
                'recipient_mobile' => null,
                'channel' => 'inapp',
                'status' => $fixture['status'],
                'error' => null,
                'meta' => json_encode(['demo_seed_key' => $seedKey]),
                'read_at' => $fixture['read_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $query = DB::table('gov_account_notifications')->where('request_id', $request->id)->where('event_type', $fixture['event'])->where('recipient_user_id', $fixture['recipient'])->where('channel', 'inapp');
            if ($query->exists()) {
                $query->update($attributes);
            } else {
                DB::table('gov_account_notifications')->insert($attributes);
            }
        }
    }

    private function seedNoticeTimeline(int $noticeId, string $marker, string $eventType): void
    {
        $query = DB::table('gov_account_timeline')->where('notice_id', $noticeId)->where('event_type', $eventType)->where('notice', $marker);
        if (! $query->exists()) {
            DB::table('gov_account_timeline')->insert(['notice_id' => $noticeId, 'event_type' => $eventType, 'notice' => $marker, 'meta' => json_encode(['seed' => true]), 'created_by' => null, 'created_by_type' => 'system', 'date' => now()]);
        }
    }

    private function seedTimeline(?int $requestId, int $branchId, string $marker, string $eventType, ?int $accountId = null): void
    {
        if (! Schema::hasTable('gov_account_timeline')) {
            return;
        }

        $query = DB::table('gov_account_timeline')->where('event_type', $eventType)->where('notice', $marker);
        if ($requestId) {
            $query->where('request_id', $requestId);
        }
        if ($accountId) {
            $query->where('account_id', $accountId);
        }
        if (! $query->exists()) {
            DB::table('gov_account_timeline')->insert(['request_id' => $requestId, 'account_id' => $accountId, 'event_type' => $eventType, 'notice' => $marker, 'meta' => json_encode(['seed' => true]), 'created_by' => null, 'created_by_type' => 'system', 'branch_id' => $branchId, 'date' => now()]);
        }
    }
}

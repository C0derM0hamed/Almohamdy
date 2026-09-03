<?php

namespace Tests\Feature;

use Database\Seeders\GovAccountDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GovAccountDemoSeederTest extends GovAccountModuleTestCase
{
    public function test_demo_fixture_is_repeatable_and_covers_review_workflows(): void
    {
        $this->seed(GovAccountDemoSeeder::class);

        $this->assertSame(15, DB::table('gov_account_requests')->where('notes', 'like', '%[GOV-ACCOUNTS-DEMO]%')->count());
        $this->assertSame(6, DB::table('gov_accounts')->where('notes', 'like', '%[GOV-ACCOUNTS-DEMO]%')->count());
        $this->assertSame(2, DB::table('gov_account_notices')->where('notes', 'like', '%[GOV-ACCOUNTS-DEMO]%')->count());
        $this->assertSame(1, DB::table('gov_account_department_heads')->where('publish', true)->count());
        $this->assertSame(9, DB::table('gov_account_requests')->where('notes', 'like', '%[GOV-ACCOUNTS-DEMO]%')->whereIn('status', ['awaiting_employee', 'under_review', 'rejected', 'approved', 'submitted_to_authority'])->count());
        $this->assertSame(1, DB::table('gov_account_requests')->where('notes', 'like', '%[GOV-ACCOUNTS-DEMO]%')->where('type', 'permission_change')->count());
        $this->assertSame(1, DB::table('gov_account_requests')->where('notes', 'like', '%[GOV-ACCOUNTS-DEMO]%')->where('type', 'suspend')->count());
        $this->assertSame(1, DB::table('gov_account_requests')->where('notes', 'like', '%[GOV-ACCOUNTS-DEMO]%')->where('type', 'close')->count());
        $this->assertGreaterThanOrEqual(1, DB::table('gov_account_undertakings')->count());
        $this->assertGreaterThanOrEqual(1, DB::table('gov_account_notice_recipients')->count());
        $this->assertGreaterThanOrEqual(1, DB::table('gov_account_timeline')->count());
        $this->assertSame(5, DB::table('gov_account_notifications')->where('channel', 'inapp')->count());
        $this->assertSame(1, DB::table('gov_account_notice_recipients')->where('view_count', 1)->count());
        $sentNoticeId = (int) DB::table('gov_account_notices')->where('notes', '[GOV-ACCOUNTS-DEMO] notice-sent')->value('id');
        $attachments = DB::table('gov_account_attachments')->where('notice_id', $sentNoticeId)->get();
        $this->assertCount(3, $attachments);
        foreach ($attachments as $attachment) {
            $this->assertTrue(Storage::disk('local')->exists($attachment->file_path));
        }

        $counts = [
            'requests' => DB::table('gov_account_requests')->count(),
            'accounts' => DB::table('gov_accounts')->count(),
            'notices' => DB::table('gov_account_notices')->count(),
            'attachments' => DB::table('gov_account_attachments')->count(),
            'timeline' => DB::table('gov_account_timeline')->count(),
        ];

        $this->seed(GovAccountDemoSeeder::class);

        $this->assertSame($counts['requests'], DB::table('gov_account_requests')->count());
        $this->assertSame($counts['accounts'], DB::table('gov_accounts')->count());
        $this->assertSame($counts['notices'], DB::table('gov_account_notices')->count());
        $this->assertSame($counts['attachments'], DB::table('gov_account_attachments')->count());
        $this->assertSame($counts['timeline'], DB::table('gov_account_timeline')->count());
    }
}

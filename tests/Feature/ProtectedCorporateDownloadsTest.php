<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProtectedCorporateDownloadsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['url']->forceRootUrl('http://localhost');
        config(['hm.permissions.bypass' => false, 'hm.permissions.admin_levels' => [3]]);

        $this->createSchema();
        $this->seedReferenceRows();
        $this->seedUser();

        Storage::disk('public')->put('secure/arabic.pdf', '%PDF-1.4 protected');
    }

    public function test_protected_downloads_preserve_arabic_filename_and_mime_type(): void
    {
        $routes = [
            ['modules.government-circulars.download', [1]],
            ['modules.inspection-visits.attachments.download', [1, 1]],
            ['modules.data-requests.attachments.download', [1, 1]],
            ['modules.correspondence.attachments.download', [1, 1]],
            ['modules.outgoing-correspondence.attachments.download', [1, 1]],
        ];

        foreach ($routes as [$route, $parameters]) {
            $response = $this
                ->withSession($this->sessionForUser())
                ->get(route($route, $parameters));

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('content-type'));
            $this->assertStringContainsString("filename*=utf-8''", (string) $response->headers->get('content-disposition'));
            $this->assertStringNotContainsString('/storage/', (string) $response->headers->get('content-disposition'));
        }
    }

    public function test_downloads_require_the_page_permission(): void
    {
        DB::table('user_permission')->where('page', 'government_circulars')->update(['permit' => '1']);

        $this
            ->withSession($this->sessionForUser())
            ->get(route('modules.government-circulars.download', 1))
            ->assertForbidden();
    }

    public function test_downloads_enforce_branch_scope(): void
    {
        $this
            ->withSession($this->sessionForUser())
            ->get(route('modules.government-circulars.download', 2))
            ->assertNotFound();
    }

    public function test_downloads_prevent_attachment_idor(): void
    {
        $this
            ->withSession($this->sessionForUser())
            ->get(route('modules.inspection-visits.attachments.download', [1, 2]))
            ->assertNotFound();
    }

    public function test_downloads_reject_path_traversal_paths(): void
    {
        $this
            ->withSession($this->sessionForUser())
            ->get(route('modules.data-requests.answers.download', [1, 2]))
            ->assertNotFound();
    }

    private function createSchema(): void
    {
        foreach ([
            'corporate_communications_outgoing_letters_attachments',
            'corporate_communications_outgoing_letters_supplementary',
            'corporate_communications_outgoing_letters_timeline',
            'corporate_communications_outgoing_letters',
            'corporate_communications_outgoing_letters_status',
            'corporate_communications_attachments',
            'corporate_communications_receipt_reports',
            'corporate_communications_timeline',
            'corporate_communications',
            'corporate_communications_status',
            'corporate_communications_sectors',
            'corporate_communications_sendertitle',
            'g_filesanswer',
            'g_filesmail',
            'g_timestatus',
            'g_view',
            'g_data',
            'g_status',
            'g_requestdelivry',
            'g_sectionsub',
            'g_sections',
            'government_inspection_visits_reply_submission',
            'government_inspection_visits_returned',
            'government_inspection_visits_receipt_reports',
            'government_inspection_visits_timeline',
            'government_inspection_visits_attachments',
            'government_inspection_visits_abuses_and_notes',
            'government_inspection_visits',
            'government_inspection_visits_numbers',
            'government_inspection_visits_types',
            'government_inspection_status',
            'government_circulars_attachments',
            'government_circulars_receipt_reports',
            'government_circulars',
            'government_circulars_status',
            'government_circulars_notification_type',
            'government_circulars_receiving_mechanism',
            'government_circulars_issuing_authority_classification',
            'government_circulars_issuing_authority',
            'government_circulars_sections_administrators',
            'government_circulars_sections',
            'branches',
            'ra_users',
            'user_permission',
            'user_groups_permission',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('ra_users', function (Blueprint $table) {
            $table->increments('hr_id');
            $table->string('hr_first_name')->nullable();
            $table->string('hr_last_name')->nullable();
            $table->string('hr_email_address')->nullable();
            $table->string('hr_username')->nullable();
            $table->string('hr_password')->nullable();
            $table->integer('hr_user_level')->default(1);
            $table->integer('branch_id')->default(10);
            $table->integer('companies_groups_id')->default(1);
            $table->integer('groupid')->default(0);
            $table->string('mobile')->nullable();
            $table->string('activated')->default('1');
            $table->timestamps();
        });
        Schema::create('user_permission', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('userid');
            $table->integer('pageid')->default(0);
            $table->string('page');
            $table->string('permit');
        });
        Schema::create('user_groups_permission', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('groupid');
            $table->string('page');
            $table->string('permit');
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->integer('companies_groups_id')->default(1);
            $table->integer('publish')->default(1);
            $table->integer('ranking')->default(1);
        });
        Schema::create('government_circulars_sections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->integer('publish')->default(1);
            $table->integer('ranking')->default(1);
        });
        Schema::create('government_circulars_sections_administrators', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('government_circulars_sections_id')->default(1);
            $table->string('administrator')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->integer('government_circulars_sections_administrators_types_id')->default(1);
            $table->integer('companies_groups_id')->default(1);
            $table->integer('publish')->default(1);
        });
        Schema::create('government_circulars_issuing_authority', fn (Blueprint $table) => $this->namedTable($table, true));
        Schema::create('government_circulars_issuing_authority_classification', function (Blueprint $table) {
            $this->namedTable($table, true);
            $table->integer('government_circulars_issuing_authority_id')->default(1);
        });
        Schema::create('government_circulars_receiving_mechanism', fn (Blueprint $table) => $this->namedTable($table, true));
        Schema::create('government_circulars_notification_type', fn (Blueprint $table) => $this->namedTable($table, true));
        Schema::create('government_circulars_status', fn (Blueprint $table) => $this->namedTable($table, true, true));
        Schema::create('government_inspection_status', fn (Blueprint $table) => $this->namedTable($table, true, true));
        Schema::create('government_inspection_visits_types', fn (Blueprint $table) => $this->namedTable($table, true));
        Schema::create('corporate_communications_status', fn (Blueprint $table) => $this->namedTable($table, true, true));
        Schema::create('corporate_communications_outgoing_letters_status', fn (Blueprint $table) => $this->namedTable($table, true, true));
        Schema::create('corporate_communications_sectors', fn (Blueprint $table) => $this->namedTable($table, true));
        Schema::create('corporate_communications_sendertitle', fn (Blueprint $table) => $this->namedTable($table, true));
        Schema::create('g_sections', fn (Blueprint $table) => $this->simpleNamedTable($table));
        Schema::create('g_sectionsub', function (Blueprint $table) {
            $this->simpleNamedTable($table);
            $table->integer('id_sub')->default(1);
        });
        Schema::create('g_requestdelivry', fn (Blueprint $table) => $this->simpleNamedTable($table));
        Schema::create('g_status', fn (Blueprint $table) => $this->simpleNamedTable($table));

        $this->createCircularTables();
        $this->createInspectionTables();
        $this->createDataRequestTables();
        $this->createCorrespondenceTables();
    }

    private function createCircularTables(): void
    {
        Schema::create('government_circulars', function (Blueprint $table) {
            $table->increments('id');
            $table->string('date')->nullable();
            $table->integer('branch_id');
            $table->integer('government_circulars_issuing_authority_id')->default(1);
            $table->integer('government_circulars_issuing_authority_classification_id')->default(1);
            $table->dateTime('issue_date')->nullable();
            $table->dateTime('received_date')->nullable();
            $table->integer('government_circulars_receiving_mechanism_id')->default(1);
            $table->string('subject')->nullable();
            $table->string('government_circulars_sections_id')->default('1');
            $table->string('circulars_file')->nullable();
            $table->integer('created_by')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->integer('companies_groups_id')->default(1);
            $table->integer('status')->default(1);
            $table->string('sms_tocken')->nullable();
            $table->string('notification_type')->nullable();
            $table->string('attachment_type')->nullable();
        });
        Schema::create('government_circulars_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('government_circulars_id');
            $table->string('circulars_file');
        });
        Schema::create('government_circulars_receipt_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('government_circulars_id');
            $table->integer('government_circulars_sections_administrators_id');
            $table->dateTime('seen_by_sms_at')->nullable();
            $table->dateTime('seen_by_email_at')->nullable();
        });
    }

    private function createInspectionTables(): void
    {
        Schema::create('government_inspection_visits_numbers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('companies_groups_id')->default(1);
            $table->integer('branch_id')->default(10);
            $table->string('subject')->nullable();
            $table->string('abuses_status')->nullable();
            $table->string('notes_status')->nullable();
            $table->string('representative_name')->nullable();
            $table->text('report')->nullable();
        });
        Schema::create('government_inspection_visits', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('visit_number')->default(1);
            $table->string('date')->nullable();
            $table->integer('branch_id');
            $table->integer('government_circulars_issuing_authority_id')->default(1);
            $table->integer('visit_type')->default(1);
            $table->dateTime('visit_date')->nullable();
            $table->dateTime('reply_time')->nullable();
            $table->string('government_circulars_sections_id')->default('1');
            $table->string('users')->nullable();
            $table->integer('created_by')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->integer('companies_groups_id')->default(1);
            $table->integer('status')->default(1);
            $table->integer('government_inspection_visits_types_id')->default(1);
        });
        Schema::create('government_inspection_visits_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('government_inspection_visits_id');
            $table->string('file_name');
            $table->string('name')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->integer('created_by')->nullable();
        });
        Schema::create('government_inspection_visits_reply_submission', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('government_inspection_visits_id');
            $table->dateTime('date')->nullable();
            $table->string('attachment_type')->nullable();
            $table->string('file_name');
            $table->dateTime('created_at')->nullable();
            $table->integer('created_by')->nullable();
        });
        foreach (['government_inspection_visits_abuses_and_notes', 'government_inspection_visits_returned', 'government_inspection_visits_receipt_reports', 'government_inspection_visits_timeline'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('government_inspection_visits_id')->nullable();
                $table->integer('government_inspection_visits_abuses_and_notes_id')->nullable();
                $table->integer('government_circulars_sections_administrators_id')->nullable();
                $table->integer('status_id')->nullable();
                $table->string('status')->nullable();
            });
        }
    }

    private function createDataRequestTables(): void
    {
        Schema::create('g_data', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_siction')->default(1);
            $table->integer('id_subsiction')->default(1);
            $table->integer('companies_groups_id')->default(1);
            $table->integer('branch_id');
            $table->string('subjec')->nullable();
            $table->date('date')->nullable();
            $table->date('Date_receipt')->nullable();
            $table->integer('Request_Receipt')->default(1);
            $table->string('send_Section')->default('1');
            $table->date('Data_delivery')->nullable();
            $table->integer('status')->default(6);
            $table->integer('userid')->default(1);
            $table->string('create_at')->nullable();
            $table->string('Reminderـtime')->nullable();
            $table->string('c')->nullable();
            $table->string('becuse')->nullable();
            $table->text('AnswerText')->nullable();
        });
        Schema::create('g_filesmail', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('file');
            $table->string('id_data');
        });
        Schema::create('g_filesanswer', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('file');
            $table->string('id_data');
        });
        foreach (['g_timestatus', 'g_view'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->string('id_data')->nullable();
                $table->integer('status')->nullable();
                $table->integer('userid')->nullable();
            });
        }
    }

    private function createCorrespondenceTables(): void
    {
        foreach (['corporate_communications', 'corporate_communications_outgoing_letters'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName) {
                $table->increments('id');
                $table->string('date')->nullable();
                $table->integer('branch_id');
                $table->integer('sectors_id')->default(1);
                $table->integer('government_circulars_issuing_authority_id')->default(1);
                $table->integer('corporate_communications_senderTitle_id')->default(1);
                $table->dateTime('issue_date')->nullable();
                if ($tableName === 'corporate_communications') {
                    $table->dateTime('received_date')->nullable();
                    $table->string('government_circulars_sections_id')->default('1');
                } else {
                    $table->text('letter_content')->nullable();
                    $table->integer('registration_number')->default(1);
                    $table->integer('year')->default(2026);
                }
                $table->integer('government_circulars_receiving_mechanism_id')->default(1);
                $table->string('sender_gender')->nullable();
                $table->string('sender')->nullable();
                $table->string('job_title')->nullable();
                $table->string('type')->nullable();
                $table->dateTime('receiving_response_date')->nullable();
                $table->integer('created_by')->default(1);
                $table->dateTime('created_at')->nullable();
                $table->integer('companies_groups_id')->default(1);
                $table->integer('status')->default(1);
                $table->string('replied_status')->nullable();
                $table->string('sms_tocken')->nullable();
                $table->integer('document_status')->default(1);
                $table->string('attachment_type')->nullable();
            });
        }
        Schema::create('corporate_communications_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('corporate_communications_id');
            $table->string('file');
        });
        Schema::create('corporate_communications_outgoing_letters_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('corporate_communications_outgoing_letters_id');
            $table->string('file');
            $table->string('file_name')->nullable();
        });
        foreach (['corporate_communications_receipt_reports', 'corporate_communications_timeline', 'corporate_communications_outgoing_letters_timeline', 'corporate_communications_outgoing_letters_supplementary'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('corporate_communications_id')->nullable();
                $table->integer('corporate_communications_outgoing_letters_id')->nullable();
                $table->integer('government_circulars_sections_administrators_id')->nullable();
                $table->integer('status_id')->nullable();
            });
        }
    }

    private function seedReferenceRows(): void
    {
        DB::table('branches')->insert([
            ['id' => 10, 'name_en' => 'Branch A', 'name_ar' => 'الفرع أ', 'companies_groups_id' => 1],
            ['id' => 20, 'name_en' => 'Branch B', 'name_ar' => 'الفرع ب', 'companies_groups_id' => 1],
        ]);

        foreach ([
            'government_circulars_sections',
            'government_circulars_issuing_authority',
            'government_circulars_issuing_authority_classification',
            'government_circulars_receiving_mechanism',
            'government_circulars_notification_type',
            'government_circulars_status',
            'government_inspection_status',
            'government_inspection_visits_types',
            'corporate_communications_status',
            'corporate_communications_outgoing_letters_status',
            'corporate_communications_sectors',
            'corporate_communications_sendertitle',
        ] as $table) {
            DB::table($table)->insert(['id' => 1, 'name_en' => 'Name', 'name_ar' => 'اسم', 'publish' => 1, 'ranking' => 1]);
        }

        foreach (['g_sections', 'g_sectionsub', 'g_requestdelivry', 'g_status'] as $table) {
            DB::table($table)->insert(['id' => 1, 'name' => 'اسم']);
        }

        DB::table('government_inspection_visits_numbers')->insert(['id' => 1, 'branch_id' => 10, 'companies_groups_id' => 1, 'subject' => 'زيارة']);
        DB::table('government_circulars')->insert([
            ['id' => 1, 'branch_id' => 10, 'companies_groups_id' => 1, 'subject' => 'ملف عربي', 'circulars_file' => 'secure/arabic.pdf'],
            ['id' => 2, 'branch_id' => 20, 'companies_groups_id' => 1, 'subject' => 'فرع آخر', 'circulars_file' => 'secure/arabic.pdf'],
        ]);
        DB::table('government_inspection_visits')->insert([
            ['id' => 1, 'branch_id' => 10, 'companies_groups_id' => 1],
            ['id' => 2, 'branch_id' => 10, 'companies_groups_id' => 1],
        ]);
        DB::table('government_inspection_visits_attachments')->insert([
            ['id' => 1, 'government_inspection_visits_id' => 1, 'file_name' => 'secure/arabic.pdf', 'name' => 'مرفق زيارة'],
            ['id' => 2, 'government_inspection_visits_id' => 2, 'file_name' => 'secure/arabic.pdf', 'name' => 'مرفق آخر'],
        ]);
        DB::table('g_data')->insert(['id' => 1, 'branch_id' => 10, 'companies_groups_id' => 1, 'subjec' => 'طلب']);
        DB::table('g_filesmail')->insert(['id' => 1, 'id_data' => '1', 'name' => 'مرفق طلب بيانات', 'file' => 'secure/arabic.pdf']);
        DB::table('g_filesanswer')->insert(['id' => 2, 'id_data' => '1', 'name' => 'ملف خطر', 'file' => '../secret.pdf']);
        DB::table('corporate_communications')->insert(['id' => 1, 'branch_id' => 10, 'companies_groups_id' => 1, 'type' => 'وارد']);
        DB::table('corporate_communications_attachments')->insert(['id' => 1, 'corporate_communications_id' => 1, 'file' => 'secure/arabic.pdf']);
        DB::table('corporate_communications_outgoing_letters')->insert(['id' => 1, 'branch_id' => 10, 'companies_groups_id' => 1, 'type' => 'صادر']);
        DB::table('corporate_communications_outgoing_letters_attachments')->insert(['id' => 1, 'corporate_communications_outgoing_letters_id' => 1, 'file' => 'secure/arabic.pdf', 'file_name' => 'مرفق صادر']);
    }

    private function seedUser(): void
    {
        DB::table('ra_users')->insert([
            'hr_id' => 1,
            'hr_first_name' => 'Audit',
            'hr_username' => 'audit',
            'hr_user_level' => 1,
            'branch_id' => 10,
            'companies_groups_id' => 1,
            'groupid' => 0,
            'activated' => '1',
        ]);

        foreach (['government_circulars', 'government_inspection_visits', 'Governmentـreportss', 'corporate_communications', 'corporate_communications_outgoing_letters'] as $page) {
            DB::table('user_permission')->insert(['userid' => 1, 'page' => $page, 'permit' => '2']);
        }
    }

    private function sessionForUser(): array
    {
        return [
            'hr_user_id' => 1,
            'hr_user_level' => 1,
            'companies_groups_id' => 1,
            'hr_branch_id' => 10,
            'groupid' => 0,
        ];
    }

    private function namedTable(Blueprint $table, bool $publish = false, bool $ranking = false): void
    {
        $table->increments('id');
        $table->string('name_en')->nullable();
        $table->string('name_ar')->nullable();
        if ($publish) {
            $table->integer('publish')->default(1);
        }
        $table->integer('ranking')->default(1);
        $table->string('info')->nullable();
    }

    private function simpleNamedTable(Blueprint $table): void
    {
        $table->increments('id');
        $table->string('name')->nullable();
    }
}

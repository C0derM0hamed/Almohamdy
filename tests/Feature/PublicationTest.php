<?php

namespace Tests\Feature;

use App\Services\Publications\PublicationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']); app('db')->purge('sqlite');
        Schema::create('branches', function (Blueprint $b): void { $b->increments('id'); $b->integer('companies_groups_id'); $b->string('name_ar'); $b->integer('publish')->default(1); });
        Schema::create('post_type', function (Blueprint $b): void { $b->increments('id'); $b->string('name_ar'); $b->integer('publish')->default(1); });
        Schema::create('new_post', function (Blueprint $b): void { $b->increments('id'); $b->integer('branch_id'); $b->integer('post_type_id'); $b->string('subject_ar'); $b->string('subject_en')->nullable(); $b->text('post_ar'); $b->text('post_en')->nullable(); $b->string('uploaded_file')->nullable(); $b->integer('companies_groups_id'); $b->integer('created_by'); });
        DB::table('branches')->insert([['id' => 1, 'companies_groups_id' => 1, 'name_ar' => 'الفرع أ', 'publish' => 1], ['id' => 2, 'companies_groups_id' => 1, 'name_ar' => 'الفرع ب', 'publish' => 1]]);
        DB::table('post_type')->insert(['id' => 1, 'name_ar' => 'تعميم', 'publish' => 1]);
        session(['hr_user_id' => 10, 'hr_user_level' => 3, 'hr_branch_id' => 1, 'companies_groups_id' => 1]); Storage::fake('public');
    }

    public function test_publication_is_created_for_scoped_branches_and_hidden_cross_branch(): void
    {
        $service = app(PublicationService::class);
        $id = $service->create(['branch_ids' => [1, 2], 'post_type_id' => 1, 'subject_ar' => 'تعميم', 'subject_en' => '', 'post_ar' => 'النص', 'post_en' => ''], UploadedFile::fake()->create('notice.png', 10, 'image/png'));
        $this->assertDatabaseCount('new_post', 2);
        session(['hr_user_level' => 2]);
        $own = DB::table('new_post')->where('branch_id', 1)->first();
        $this->assertNotNull($service->find($own->id));
        $other = DB::table('new_post')->where('branch_id', 2)->first();
        $this->assertNull($service->find($other->id));
    }
}

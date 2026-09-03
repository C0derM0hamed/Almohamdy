<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permission_change_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->unsignedBigInteger('subject_user_id')->index();
            $table->string('action', 80);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('added_permissions')->nullable();
            $table->json('removed_permissions')->nullable();
            $table->uuid('request_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('permission_legacy_archive', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_table', 80);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('page', 255)->nullable();
            $table->string('permit', 20)->nullable();
            $table->string('reason', 120);
            $table->timestamp('archived_at')->useCurrent();
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Archive orphan group grants before removing them from the active set.
        if (Schema::hasTable('user_groups_permission') && Schema::hasTable('user_groups')) {
            $orphans = DB::table('user_groups_permission as p')->leftJoin('user_groups as g', 'g.id', '=', 'p.groupid')
                ->whereNull('g.id')->get(['p.id', 'p.groupid', 'p.page', 'p.permit']);
            foreach ($orphans as $row) {
                DB::table('permission_legacy_archive')->insert([
                    'source_table' => 'user_groups_permission', 'source_id' => $row->id, 'subject_id' => $row->groupid,
                    'page' => $row->page, 'permit' => $row->permit, 'reason' => 'orphan_group', 'archived_at' => now(),
                ]);
            }
            if ($orphans->isNotEmpty()) {
                DB::table('user_groups_permission')->whereIn('id', $orphans->pluck('id'))->delete();
            }
        }

        foreach (['user_permission' => ['userid', 'user_permission_subject_page_unique'], 'user_groups_permission' => ['groupid', 'user_groups_permission_subject_page_unique']] as $table => [$subject, $index]) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            $duplicates = DB::table($table)->select($subject, 'page', DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
                ->groupBy($subject, 'page')->havingRaw('COUNT(*) > 1')->get();
            foreach ($duplicates as $duplicate) {
                $ids = array_map('intval', explode(',', $duplicate->ids));
                $rows = DB::table($table)->whereIn('id', $ids)->get(['id', 'permit']);
                $winner = $rows->first(fn ($row): bool => (string) $row->permit === '1') ?? $rows->first(fn ($row): bool => (string) $row->permit === '2') ?? $rows->first();
                foreach ($rows->where('id', '!=', $winner->id) as $row) {
                    DB::table('permission_legacy_archive')->insert(['source_table' => $table, 'source_id' => $row->id, 'subject_id' => $duplicate->{$subject}, 'page' => $duplicate->page, 'permit' => $row->permit, 'reason' => 'duplicate_permission', 'archived_at' => now()]);
                }
                DB::table($table)->whereIn('id', $rows->pluck('id')->reject(fn ($id) => (int) $id === (int) $winner->id))->delete();
            }
            Schema::table($table, function (Blueprint $blueprint) use ($subject, $index): void {
                $blueprint->unique([$subject, 'page'], $index);
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach (['user_permission' => 'user_permission_subject_page_unique', 'user_groups_permission' => 'user_groups_permission_subject_page_unique'] as $table => $index) {
                if (Schema::hasTable($table)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($index));
                }
            }
        }
        Schema::dropIfExists('permission_legacy_archive');
        Schema::dropIfExists('permission_change_logs');
    }
};

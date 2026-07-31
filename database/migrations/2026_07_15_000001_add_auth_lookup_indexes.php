<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('ra_users', 'ra_users_hr_email_address_index', function () {
            Schema::table('ra_users', function (Blueprint $table) {
                $table->index('hr_email_address', 'ra_users_hr_email_address_index');
            });
        });

        $this->addIndexIfMissing('ra_users', 'ra_users_last_failed_login_index', function () {
            Schema::table('ra_users', function (Blueprint $table) {
                $table->index('last_failed_login', 'ra_users_last_failed_login_index');
            });
        });

        $this->addIndexIfMissing('user_permission', 'user_permission_userid_index', function () {
            Schema::table('user_permission', function (Blueprint $table) {
                $table->index('userid', 'user_permission_userid_index');
            });
        });

        $this->addIndexIfMissing('user_groups_permission', 'user_groups_permission_groupid_index', function () {
            Schema::table('user_groups_permission', function (Blueprint $table) {
                $table->index('groupid', 'user_groups_permission_groupid_index');
            });
        });

        // Redundant secondary key duplicates PRIMARY on MyISAM dumps.
        if ($this->indexExists('ra_users', 'hr_id')) {
            Schema::table('ra_users', function (Blueprint $table) {
                $table->dropIndex('hr_id');
            });
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('ra_users', 'ra_users_hr_email_address_index');
        $this->dropIndexIfExists('ra_users', 'ra_users_last_failed_login_index');
        $this->dropIndexIfExists('user_permission', 'user_permission_userid_index');
        $this->dropIndexIfExists('user_groups_permission', 'user_groups_permission_groupid_index');

        if (! $this->indexExists('ra_users', 'hr_id')) {
            Schema::table('ra_users', function (Blueprint $table) {
                $table->index('hr_id', 'hr_id');
            });
        }
    }

    private function addIndexIfMissing(string $table, string $index, callable $callback): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $index)) {
            return;
        }

        $callback();
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropIndex($index);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        $count = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->count();

        return $count > 0;
    }
};

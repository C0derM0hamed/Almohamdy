<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ra_user_branches')) {
            Schema::create('ra_user_branches', function (Blueprint $table): void {
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('branch_id');
                $table->timestamps();

                $table->primary(['user_id', 'branch_id']);
                $table->index('branch_id');
            });
        }

        if (! Schema::hasTable('ra_users')) {
            return;
        }

        DB::table('ra_users')
            ->select(['hr_id', 'branch_id'])
            ->whereNotNull('branch_id')
            ->where('branch_id', '>', 0)
            ->orderBy('hr_id')
            ->chunkById(500, function ($users): void {
                $now = now();
                $rows = $users->map(static fn ($user): array => [
                    'user_id' => (int) $user->hr_id,
                    'branch_id' => (int) $user->branch_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows !== []) {
                    DB::table('ra_user_branches')->insertOrIgnore($rows);
                }
            }, 'hr_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('ra_user_branches');
    }
};

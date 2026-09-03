<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'gov_accounts.view', 'gov_accounts.request', 'gov_accounts.process',
        'gov_accounts.hr', 'gov_accounts.export', 'gov_accounts_admin',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('ra_users') || ! Schema::hasTable('user_permission')) {
            return;
        }

        $users = DB::table('ra_users')->where(function ($query): void {
            $query->whereIn('hr_username', [
                '18177',
                'mohamed_super', 'mohamed_permission', 'mohamed_brancha', 'mohamed_branchb',
                'bader_super', 'bader_permission', 'bader_brancha', 'bader_branchb',
            ])->orWhereIn('hr_first_name', ['Mohamed', 'Bader', 'محمد', 'بدر'])
                ->orWhereIn('hr_last_name', ['Mohamed', 'Bader', 'محمد', 'بدر']);
        })->pluck('hr_id');

        foreach ($users as $userId) {
            foreach ($this->permissions as $permission) {
                DB::table('user_permission')->updateOrInsert(
                    ['userid' => (int) $userId, 'page' => $permission],
                    ['pageid' => 0, 'permit' => '2'],
                );
            }
        }
    }

    public function down(): void
    {
        // Explicit grants remain intact on rollback, matching the approved License pattern.
    }
};

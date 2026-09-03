<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'licenses.view',
        'licenses.process',
        'licenses.export',
        'licenses_admin',
        'licenses_finance',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('ra_users') || ! Schema::hasTable('user_permission')) {
            return;
        }

        $users = DB::table('ra_users')
            ->where(function ($query): void {
                $query->whereIn('hr_username', [
                    '18177',
                    'mohamed_super', 'mohamed_permission', 'mohamed_brancha', 'mohamed_branchb',
                    'bader_super', 'bader_permission', 'bader_brancha', 'bader_branchb',
                ])
                    ->orWhereIn('hr_first_name', ['Mohamed', 'Bader', 'محمد', 'بدر'])
                    ->orWhereIn('hr_last_name', ['Mohamed', 'Bader', 'محمد', 'بدر']);
            })
            ->pluck('hr_id');

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
        // Keep explicit grants intact if the migration is rolled back.
    }
};

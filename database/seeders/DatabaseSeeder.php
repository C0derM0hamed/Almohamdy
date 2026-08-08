<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LocalDemoUsersSeeder::class);
        $this->call(ClientTestUsersSeeder::class);
        $this->call(AuthorizationSeeder::class);
        // Runs last: it mirrors branch scope from the audit accounts, which
        // AuthorizationSeeder has just finished granting.
        $this->call(ClientDemoUsersSeeder::class);
    }
}

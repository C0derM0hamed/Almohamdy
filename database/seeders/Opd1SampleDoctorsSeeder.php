<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Opd1SampleDoctorsSeeder extends Seeder
{
    public function run(): void
    {
        app(OpdSampleDoctorsSeeder::class)->seedOpd(1);
    }
}

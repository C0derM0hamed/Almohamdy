<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Opd3SampleDoctorsSeeder extends Seeder
{
    public function run(): void
    {
        app(OpdSampleDoctorsSeeder::class)->seedOpd(3);
    }
}

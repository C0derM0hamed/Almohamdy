<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inquiries_and_services') && ! Schema::hasColumn('inquiries_and_services', 'assigned_to')) {
            Schema::table('inquiries_and_services', function (Blueprint $table) {
                $table->unsignedInteger('assigned_to')->nullable()->after('job_title');
            });
        }

        $exists = DB::table('inquiries_and_services_status')->where('id', 6)->exists();

        if (! $exists) {
            DB::table('inquiries_and_services_status')->insert([
                'id' => 6,
                'name_en' => 'Completed',
                'name_ar' => 'مكتمل',
                'name_ch' => '',
                'info' => '#86efac',
                'publish' => 1,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inquiries_and_services') && Schema::hasColumn('inquiries_and_services', 'assigned_to')) {
            Schema::table('inquiries_and_services', function (Blueprint $table) {
                $table->dropColumn('assigned_to');
            });
        }

        DB::table('inquiries_and_services_status')->where('id', 6)->delete();
    }
};

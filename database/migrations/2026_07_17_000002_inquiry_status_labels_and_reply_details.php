<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inquiries_and_services_reply')) {
            Schema::table('inquiries_and_services_reply', function (Blueprint $table) {
                $table->text('inquiry_details')->nullable()->change();
            });
        }

        $updates = [
            3 => ['name_en' => 'Under Review', 'name_ar' => 'تحت المراجعة', 'info' => '#a9d6e5'],
            4 => ['name_en' => 'Contacted', 'name_ar' => 'تم التواصل', 'info' => '#cccccc'],
            5 => [
                'name_en' => 'Contacted – No Response from Patient',
                'name_ar' => 'تم التواصل ولم يتم الرد من قبل المراجع',
                'info' => '#cfe1b9',
            ],
            6 => ['name_en' => 'Completed', 'name_ar' => 'مكتمل', 'info' => '#86efac'],
        ];

        foreach ($updates as $id => $values) {
            DB::table('inquiries_and_services_status')->where('id', $id)->update($values);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inquiries_and_services_reply')) {
            Schema::table('inquiries_and_services_reply', function (Blueprint $table) {
                $table->string('inquiry_details', 200)->nullable()->change();
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The legacy admission calculators persisted these fields, but the
     * initial NewProject schema was created without them.  Keep this
     * migration conditional because some installations receive the legacy
     * tables from the existing database rather than from Laravel migrations.
     */
    public function up(): void
    {
        foreach (['admission_calculator', 'manual_admission_calculator'] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'room_type')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedBigInteger('room_type')->default(0)->index();
            });
        }

        if (Schema::hasTable('manual_admission_calculator_procedures')
            && ! Schema::hasColumn('manual_admission_calculator_procedures', 'pharmaceutical')) {
            Schema::table('manual_admission_calculator_procedures', function (Blueprint $blueprint): void {
                $blueprint->string('pharmaceutical', 50)->default('0');
            });
        }
    }

    public function down(): void
    {
        foreach (['admission_calculator', 'manual_admission_calculator'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'room_type')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('room_type');
                });
            }
        }

        if (Schema::hasTable('manual_admission_calculator_procedures')
            && Schema::hasColumn('manual_admission_calculator_procedures', 'pharmaceutical')) {
            Schema::table('manual_admission_calculator_procedures', function (Blueprint $blueprint): void {
                $blueprint->dropColumn('pharmaceutical');
            });
        }
    }
};

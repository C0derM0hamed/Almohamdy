<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('receive_unidentified_case') && ! Schema::hasColumn('receive_unidentified_case', 'number')) {
            Schema::table('receive_unidentified_case', fn (Blueprint $table) => $table->string('number', 50)->nullable()->after('date_time'));
        }
        foreach (['escape_report_form_attachments', 'incident_report_form_attachments', 'health_service_purchase_form_attachments'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table): void {
                    $table->bigIncrements('id');
                    $table->unsignedBigInteger('form_id')->index();
                    $table->string('file_name', 200);
                    $table->unsignedBigInteger('created_by');
                    $table->timestamp('created_at')->useCurrent();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['escape_report_form_attachments', 'incident_report_form_attachments', 'health_service_purchase_form_attachments'] as $table) Schema::dropIfExists($table);
        if (Schema::hasTable('receive_unidentified_case') && Schema::hasColumn('receive_unidentified_case', 'number')) Schema::table('receive_unidentified_case', fn (Blueprint $table) => $table->dropColumn('number'));
    }
};

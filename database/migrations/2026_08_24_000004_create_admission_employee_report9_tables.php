<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_emp_9')) {
            Schema::create('report_emp_9', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('period')->nullable();
                $table->integer('date')->nullable();
                $table->integer('branch_id')->index();
                $table->integer('companies_groups_id')->index();
                $table->timestamp('created_at')->nullable();
                $table->integer('creator')->nullable();
                $table->boolean('publish')->default(true);
                $table->integer('updator')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->integer('rep_place')->nullable();
            });
        }

        if (! Schema::hasTable('report_emp_9_report')) {
            Schema::create('report_emp_9_report', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('report_id')->index();
                $table->integer('date')->nullable();
                $table->integer('branch_id')->index();
                $table->string('filenumber')->nullable();
                $table->string('location')->nullable();
                $table->string('room_bod_number')->nullable();
                $table->integer('section')->nullable();
                $table->integer('notice')->nullable();
                $table->integer('action')->nullable();
                $table->text('other')->nullable();
                $table->string('files')->nullable();
            });
        }

        if (! Schema::hasTable('report_emp_9_support_services')) {
            Schema::create('report_emp_9_support_services', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('report_id')->index();
                $table->integer('date')->nullable();
                $table->integer('branch_id')->index();
                $table->string('maintenance_departments')->nullable();
                $table->string('maintenance_type')->nullable();
                $table->string('maintenance_request_type')->nullable();
                $table->text('description')->nullable();
                $table->string('files')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_emp_9_support_services');
        Schema::dropIfExists('report_emp_9_report');
        Schema::dropIfExists('report_emp_9');
    }
};

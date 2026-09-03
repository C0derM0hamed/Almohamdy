<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['gov_account_authorities', 'gov_account_roles'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->increments('id');
                    $table->unsignedInteger('companies_groups_id')->index();
                    $table->string('name_ar');
                    $table->string('name_en');
                    $table->boolean('publish')->default(true)->index();
                    $table->unsignedInteger('ranking')->default(0);
                    $table->timestamps();
                    $table->index(['companies_groups_id', 'publish', 'ranking'], $tableName === 'gov_account_roles' ? 'gov_role_scope_idx' : 'gov_authority_scope_idx');
                });
            }
        }

        if (! Schema::hasTable('gov_account_services')) {
            Schema::create('gov_account_services', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->unsignedInteger('authority_id')->index();
                $table->string('name_ar');
                $table->string('name_en');
                $table->boolean('publish')->default(true)->index();
                $table->unsignedInteger('ranking')->default(0);
                $table->timestamps();
                $table->index(['companies_groups_id', 'authority_id', 'publish'], 'gov_service_scope_idx');
            });
        }

        if (! Schema::hasTable('gov_account_department_heads')) {
            Schema::create('gov_account_department_heads', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->unsignedInteger('department_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->boolean('publish')->default(true)->index();
                $table->timestamps();
                $table->unique(['department_id', 'user_id'], 'gov_dept_head_unique');
                $table->index(['companies_groups_id', 'publish'], 'gov_dept_head_scope_idx');
            });
        }

        foreach (['gov_account_request_statuses', 'gov_account_statuses'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->increments('id');
                    $table->unsignedInteger('companies_groups_id')->index();
                    $table->string('code', 40);
                    $table->string('name_ar');
                    $table->string('name_en');
                    $table->string('info', 20)->nullable();
                    $table->boolean('publish')->default(true)->index();
                    $table->unsignedInteger('ranking')->default(0);
                    $table->timestamps();
                    $table->unique(['companies_groups_id', 'code'], $tableName === 'gov_account_statuses' ? 'gov_acc_status_code_unique' : 'gov_req_status_code_unique');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gov_account_statuses');
        Schema::dropIfExists('gov_account_request_statuses');
        Schema::dropIfExists('gov_account_department_heads');
        Schema::dropIfExists('gov_account_services');
        Schema::dropIfExists('gov_account_roles');
        Schema::dropIfExists('gov_account_authorities');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gov_account_requests')) {
            Schema::create('gov_account_requests', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->unsignedInteger('branch_id')->index();
                $table->string('type', 30)->index();
                $table->string('status', 30)->index();
                $table->string('origin', 20)->index();
                $table->unsignedInteger('employee_user_id')->index();
                $table->unsignedInteger('department_id')->index();
                $table->unsignedInteger('authority_id')->index();
                $table->unsignedInteger('service_id')->index();
                $table->unsignedInteger('role_id')->index();
                $table->unsignedInteger('requested_role_id')->nullable()->index();
                $table->unsignedInteger('account_id')->nullable()->index();
                $table->text('justification');
                $table->text('notes')->nullable();
                $table->unsignedInteger('round')->default(1);
                $table->text('rejection_reason')->nullable();
                $table->unsignedInteger('reviewed_by')->nullable()->index();
                $table->dateTime('reviewed_at')->nullable();
                $table->dateTime('authority_submitted_at')->nullable();
                $table->unsignedInteger('authority_submitted_by')->nullable()->index();
                $table->string('authority_reference')->nullable();
                $table->unsignedInteger('created_by')->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['companies_groups_id', 'branch_id', 'status'], 'gov_req_scope_status_idx');
                $table->index(['employee_user_id', 'status'], 'gov_req_employee_status_idx');
            });
        }

        if (! Schema::hasTable('gov_account_undertakings')) {
            Schema::create('gov_account_undertakings', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('request_id')->index();
                $table->string('kind', 20)->index();
                $table->unsignedInteger('user_id')->index();
                $table->text('undertaking_text');
                $table->string('status', 20)->default('pending')->index();
                $table->dateTime('requested_at');
                $table->dateTime('accepted_at')->nullable();
                $table->string('ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->unique(['request_id', 'kind'], 'gov_undertaking_request_kind_unique');
            });
        }

        if (! Schema::hasTable('gov_accounts')) {
            Schema::create('gov_accounts', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->unsignedInteger('branch_id')->index();
                $table->unsignedInteger('employee_user_id')->index();
                $table->unsignedInteger('authority_id')->index();
                $table->unsignedInteger('service_id')->index();
                $table->unsignedInteger('role_id')->index();
                $table->string('username');
                $table->string('login_url', 2048)->nullable();
                $table->string('reference_no')->nullable();
                $table->string('status', 30)->index();
                $table->unsignedInteger('created_from_request_id')->index();
                $table->unsignedInteger('managed_by')->nullable()->index();
                $table->date('account_created_at');
                $table->dateTime('suspended_at')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->text('closed_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['companies_groups_id', 'branch_id', 'status'], 'gov_acc_scope_status_idx');
                $table->index(['employee_user_id', 'authority_id'], 'gov_acc_employee_authority_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gov_accounts');
        Schema::dropIfExists('gov_account_undertakings');
        Schema::dropIfExists('gov_account_requests');
    }
};

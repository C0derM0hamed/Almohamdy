<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('licenses')) {
            Schema::create('licenses', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->unsignedInteger('license_authority_id')->index();
                $table->unsignedInteger('license_type_id')->index();
                $table->string('license_number', 150)->nullable()->index();
                $table->string('title')->nullable();
                $table->unsignedInteger('responsible_user_id')->index();
                $table->date('issue_date');
                $table->date('expiry_date')->index();
                $table->unsignedInteger('status_id')->index();
                $table->unsignedInteger('renewal_stage_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->boolean('publish')->default(true)->index();
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['companies_groups_id', 'publish', 'expiry_date'], 'licenses_scope_expiry_idx');
                $table->index(['responsible_user_id', 'publish'], 'licenses_owner_publish_idx');
            });
        }

        if (! Schema::hasTable('license_branches')) {
            Schema::create('license_branches', function (Blueprint $table): void {
                $table->unsignedInteger('license_id');
                $table->unsignedInteger('branch_id');
                $table->timestamps();

                $table->primary(['license_id', 'branch_id']);
                $table->index('branch_id');
            });
        }

        if (! Schema::hasTable('license_undertakings')) {
            Schema::create('license_undertakings', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('license_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->text('undertaking_text');
                $table->string('status', 30)->default('pending')->index();
                $table->dateTime('requested_at')->index();
                $table->dateTime('accepted_at')->nullable();
                $table->dateTime('escalated_at')->nullable();
                $table->string('ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['license_id', 'user_id', 'status'], 'lic_undertaking_lookup_idx');
            });
        }

        if (! Schema::hasTable('license_renewals')) {
            Schema::create('license_renewals', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('license_id')->index();
                $table->date('previous_expiry_date');
                $table->date('new_expiry_date')->nullable();
                $table->dateTime('started_at')->index();
                $table->dateTime('completed_at')->nullable()->index();
                $table->unsignedInteger('completed_by')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['license_id', 'completed_at'], 'lic_renewal_open_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('license_renewals');
        Schema::dropIfExists('license_undertakings');
        Schema::dropIfExists('license_branches');
        Schema::dropIfExists('licenses');
    }
};

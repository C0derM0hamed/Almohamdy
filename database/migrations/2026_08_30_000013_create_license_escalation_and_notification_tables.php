<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('license_escalation_groups')) {
            Schema::create('license_escalation_groups', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->string('name');
                $table->boolean('publish')->default(true)->index();
                $table->timestamps();

                $table->index(['companies_groups_id', 'publish'], 'lic_escalation_scope_idx');
            });
        }

        if (! Schema::hasTable('license_escalation_group_members')) {
            Schema::create('license_escalation_group_members', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('group_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->timestamps();

                $table->unique(['group_id', 'user_id'], 'lic_escalation_member_unique');
            });
        }

        if (! Schema::hasTable('license_notifications')) {
            Schema::create('license_notifications', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('license_id')->nullable()->index();
                $table->unsignedInteger('payment_request_id')->nullable()->index();
                $table->string('event_type', 60)->index();
                $table->unsignedInteger('recipient_user_id')->nullable()->index();
                $table->string('recipient_email')->nullable();
                $table->string('recipient_mobile', 40)->nullable();
                $table->string('channel', 20)->index();
                $table->string('status', 20)->index();
                $table->text('error')->nullable();
                $table->text('reason')->nullable();
                $table->json('meta')->nullable();
                $table->dateTime('read_at')->nullable()->index();
                $table->dateTime('created_at')->index();

                $table->index(['license_id', 'event_type', 'channel'], 'lic_notification_dedupe_idx');
                $table->index(['recipient_user_id', 'read_at', 'created_at'], 'lic_notification_inbox_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('license_notifications');
        Schema::dropIfExists('license_escalation_group_members');
        Schema::dropIfExists('license_escalation_groups');
    }
};

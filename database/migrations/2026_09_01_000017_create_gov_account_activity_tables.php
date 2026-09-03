<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gov_account_timeline')) {
            Schema::create('gov_account_timeline', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('request_id')->nullable()->index();
                $table->unsignedInteger('account_id')->nullable()->index();
                $table->unsignedInteger('notice_id')->nullable()->index();
                $table->string('event_type', 60)->index();
                $table->unsignedInteger('status_id')->nullable()->index();
                $table->text('notice')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->string('created_by_type', 40)->default('user');
                $table->unsignedInteger('branch_id')->nullable()->index();
                $table->dateTime('date')->index();
                $table->index(['request_id', 'date'], 'gov_timeline_request_date_idx');
                $table->index(['account_id', 'date'], 'gov_timeline_account_date_idx');
            });
        }

        if (! Schema::hasTable('gov_account_attachments')) {
            Schema::create('gov_account_attachments', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('request_id')->nullable()->index();
                $table->unsignedInteger('account_id')->nullable()->index();
                $table->unsignedInteger('notice_id')->nullable()->index();
                $table->string('context', 40)->index();
                $table->string('file_path', 1024);
                $table->string('original_name');
                $table->string('mime', 150);
                $table->unsignedBigInteger('size');
                $table->text('description')->nullable();
                $table->unsignedInteger('uploaded_by')->index();
                $table->dateTime('uploaded_at')->index();
            });
        }

        if (! Schema::hasTable('gov_account_notifications')) {
            Schema::create('gov_account_notifications', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('request_id')->nullable()->index();
                $table->unsignedInteger('account_id')->nullable()->index();
                $table->unsignedInteger('notice_id')->nullable()->index();
                $table->string('event_type', 60)->index();
                $table->unsignedInteger('recipient_user_id')->nullable()->index();
                $table->string('recipient_email')->nullable()->index();
                $table->string('recipient_mobile')->nullable();
                $table->string('channel', 20)->index();
                $table->string('status', 20)->index();
                $table->text('error')->nullable();
                $table->json('meta')->nullable();
                $table->dateTime('read_at')->nullable();
                $table->timestamps();
                $table->index(['recipient_user_id', 'read_at'], 'gov_notification_inbox_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gov_account_notifications');
        Schema::dropIfExists('gov_account_attachments');
        Schema::dropIfExists('gov_account_timeline');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sits_rep')) {
            Schema::create('sits_rep', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('onid')->nullable();
                $table->string('no_lawsuit')->nullable();
                $table->string('Paymentـstatus')->nullable();
                $table->string('lawsuit_date')->nullable();
                $table->string('dates')->nullable();
                $table->integer('status')->default(1);
                $table->string('c')->nullable();
                $table->text('note')->nullable();
                $table->integer('gorup_id')->index();
                $table->integer('branch_id')->index();
                $table->integer('userid')->nullable();
                $table->integer('userid_new')->nullable();
                $table->integer('send_Section')->nullable();
                $table->text('becuse')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rep_ss')) {
            Schema::create('rep_ss', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->timestamp('create_at')->nullable();
                $table->integer('status')->default(1);
                $table->string('c')->nullable();
                $table->text('Answer')->nullable();
                $table->integer('gorup_id')->index();
                $table->integer('branch_id')->index();
                $table->integer('userid')->nullable();
                $table->integer('userid_new')->nullable();
                $table->string('countries')->nullable();
                $table->string('branches_departments')->nullable();
                $table->string('dateIn')->nullable();
                $table->string('dateOut')->nullable();
                $table->string('Patientـname')->nullable();
                $table->string('Paymentـstatus')->nullable();
                $table->string('service')->nullable();
                $table->string('no_file')->nullable();
                $table->string('onid')->nullable();
                $table->text('details')->nullable();
                $table->text('becuse')->nullable();
                $table->timestamps();
            });
        }

        // These are the original attachment tables used by rep_ss.php and
        // sit_rep2.php. The generic new workflow stores the same filename
        // values through Laravel storage and serves them through a scoped
        // download endpoint.
        if (! Schema::hasTable('rep_sfi')) {
            Schema::create('rep_sfi', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('file')->nullable();
                $table->integer('id_data')->index();
            });
        }

        if (! Schema::hasTable('sit_files')) {
            Schema::create('sit_files', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('file')->nullable();
                $table->integer('id_data')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_ss');
        Schema::dropIfExists('sits_rep');
        Schema::dropIfExists('rep_sfi');
        Schema::dropIfExists('sit_files');
    }
};

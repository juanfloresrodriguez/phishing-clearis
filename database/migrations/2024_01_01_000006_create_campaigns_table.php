<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('email_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('landing_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sending_profile_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('status', [
                'draft', 'scheduled', 'running', 'paused', 'finished', 'cancelled', 'dry_run'
            ])->default('draft');
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->time('send_window_start')->nullable(); // e.g. 09:00
            $table->time('send_window_end')->nullable();   // e.g. 17:00
            $table->boolean('respect_work_hours')->default(true);
            $table->boolean('respect_work_days')->default(true);
            $table->integer('rate_limit_per_minute')->default(10);
            $table->string('language', 5)->default('es');
            $table->json('tags')->nullable();
            $table->json('excluded_user_ids')->nullable();
            $table->boolean('is_dry_run')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('campaign_groups', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->primary(['campaign_id', 'group_id']);
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_token', 64)->unique();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('send_error')->default(false);
            $table->text('send_error_message')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'target_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaign_groups');
        Schema::dropIfExists('campaigns');
    }
};

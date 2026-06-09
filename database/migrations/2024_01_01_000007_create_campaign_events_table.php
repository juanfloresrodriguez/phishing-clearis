<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_recipient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', [
                'email_scheduled',
                'email_sent',
                'email_delivered',
                'email_opened',
                'link_clicked',
                'landing_loaded',
                'form_started',
                'form_submitted',
                'training_seen',
                'email_reported',
                'send_failed',
            ]);
            $table->string('ip_address', 45)->nullable();    // truncated/approximate
            $table->string('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();   // desktop, mobile, tablet
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->json('metadata')->nullable();            // field_name, field_type etc. (no values)
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['campaign_id', 'event_type']);
            $table->index(['target_user_id', 'campaign_id']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_events');
    }
};

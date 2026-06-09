<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sending_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('from_name');
            $table->string('from_email');
            $table->string('reply_to')->nullable();
            $table->enum('mailer', ['smtp', 'google_relay', 'sendgrid', 'mailgun'])->default('smtp');
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable(); // encrypted
            $table->enum('smtp_encryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->boolean('spf_ok')->default(false);
            $table->boolean('dkim_ok')->default(false);
            $table->boolean('dmarc_ok')->default(false);
            $table->timestamp('dns_checked_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sending_profiles');
    }
};

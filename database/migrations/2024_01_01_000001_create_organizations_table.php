<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->string('timezone')->default('UTC');
            $table->json('work_hours')->nullable(); // {"start":"09:00","end":"18:00","days":[1,2,3,4,5]}
            $table->json('settings')->nullable();
            $table->text('privacy_notice')->nullable();
            $table->integer('event_retention_days')->default(365);
            $table->boolean('anonymize_after_retention')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('organization_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('verification_token')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->boolean('allow_recipients')->default(true);
            $table->boolean('allow_sending')->default(false);
            $table->timestamps();
            $table->unique(['organization_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_domains');
        Schema::dropIfExists('organizations');
    }
};

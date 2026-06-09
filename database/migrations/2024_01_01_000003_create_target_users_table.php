<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('department')->nullable();
            $table->string('job_title')->nullable();
            $table->string('office')->nullable();
            $table->string('language', 5)->default('es');
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('excluded')->default(false);
            $table->string('import_source')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'email']);
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['static', 'dynamic'])->default('static');
            $table->json('dynamic_filters')->nullable();
            $table->timestamps();
        });

        Schema::create('group_target_user', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained()->cascadeOnDelete();
            $table->primary(['group_id', 'target_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_target_user');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('target_users');
    }
};

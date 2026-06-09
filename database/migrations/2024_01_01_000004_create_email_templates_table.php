<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->string('from_name')->nullable();
            $table->text('html_content');
            $table->text('text_content')->nullable();
            $table->enum('category', [
                'hr', 'it', 'billing', 'shipping', 'google_workspace',
                'microsoft', 'internal_vendor', 'security_notice', 'custom'
            ])->default('custom');
            $table->string('language', 5)->default('es');
            $table->json('tags')->nullable();
            $table->boolean('has_attachment_simulation')->default(false);
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

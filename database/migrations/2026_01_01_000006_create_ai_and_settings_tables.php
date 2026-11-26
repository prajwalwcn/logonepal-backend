<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver', 32)->default('openai');
            $table->string('model')->nullable();
            $table->string('image_model')->nullable();
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('priority')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });

        Schema::create('ai_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->nullable();
            $table->string('purpose', 40);
            $table->string('model')->nullable();
            $table->string('prompt_hash', 64)->nullable()->index();
            $table->string('status', 24)->default('pending');
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['purpose', 'created_at']);
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group', 40)->default('general');
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('ai_generation_logs');
        Schema::dropIfExists('ai_providers');
    }
};

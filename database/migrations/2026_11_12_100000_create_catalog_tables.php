<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 80)->unique();
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->string('support_email')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug')->unique();
            $table->string('icon', 40)->default('package');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('slug')->unique();
        });

        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('position')->default(0);
        });

        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 20)->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 80);
            $table->string('slug')->unique();
            $table->string('summary');
            $table->longText('description')->nullable();
            $table->string('icon_path')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('license_model', 20)->default('perpetual');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->string('rejection_reason')->nullable();
            $table->string('latest_version', 40)->nullable();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['status', 'is_featured']);
            $table->index(['status', 'downloads_count']);
            $table->index(['status', 'rating_average']);
            $table->index(['status', 'price_cents']);
            $table->index(['category_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('plugin_tag', function (Blueprint $table) {
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['plugin_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('platform_plugin', function (Blueprint $table) {
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->primary(['plugin_id', 'platform_id']);
            $table->index('platform_id');
        });

        Schema::create('plugin_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption', 120)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['plugin_id', 'position']);
        });

        Schema::create('plugin_versions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 20)->unique();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('version', 40);
            $table->text('changelog')->nullable();
            $table->json('game_versions')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->string('file_path');
            $table->string('file_name', 160);
            $table->unsignedBigInteger('file_size');
            $table->char('file_hash', 64);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['plugin_id', 'version']);
            $table->index(['plugin_id', 'status', 'published_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('platform_plugin_version', function (Blueprint $table) {
            $table->foreignId('plugin_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->primary(['plugin_version_id', 'platform_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->text('vendor_reply')->nullable();
            $table->timestamp('vendor_replied_at')->nullable();
            $table->timestamps();

            $table->unique(['plugin_id', 'user_id']);
            $table->index(['plugin_id', 'created_at']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['user_id', 'plugin_id']);
            $table->index('plugin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('platform_plugin_version');
        Schema::dropIfExists('plugin_versions');
        Schema::dropIfExists('plugin_screenshots');
        Schema::dropIfExists('platform_plugin');
        Schema::dropIfExists('plugin_tag');
        Schema::dropIfExists('plugins');
        Schema::dropIfExists('platforms');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('vendors');
    }
};

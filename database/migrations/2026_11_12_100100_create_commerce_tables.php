<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->decimal('percent', 5, 2);
            $table->string('scope', 10)->default('shop');
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('vendor_share_percent')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 40);
            $table->string('payment_method_label', 60);
            $table->decimal('payment_fee_percent', 5, 2)->default(0);
            $table->unsignedInteger('payment_fee_fixed_cents')->default(0);
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->json('billing')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plugin_name', 80);
            $table->string('plugin_public_id', 20);
            $table->string('license_model', 20);
            $table->unsignedInteger('original_price_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedTinyInteger('vendor_discount_share')->default(100);

            $table->index(['vendor_id', 'order_id']);
            $table->index('plugin_id');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('billing')->nullable();
            $table->unsignedInteger('total_cents');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['user_id', 'issued_at']);
        });

        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('units');
            $table->unsignedInteger('gross_cents');
            $table->unsignedInteger('vendor_discount_cents')->default(0);
            $table->unsignedInteger('platform_discount_cents')->default(0);
            $table->unsignedInteger('payment_fee_cents')->default(0);
            $table->unsignedInteger('platform_fee_cents')->default(0);
            $table->integer('net_cents');
            $table->string('status', 20)->default('pending');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('paid_out_at')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'order_id']);
            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('sales');
    }
};

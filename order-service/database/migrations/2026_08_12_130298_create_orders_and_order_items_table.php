<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->nullable()->unique();
            $table->string('order_code', 50)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index(); 
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');
            $table->string('shipping_name');
            $table->string('shipping_phone', 15);
            $table->string('phone', 30)->nullable();
            $table->text('shipping_address');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_fee', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2);
            $table->string('status', 30)->default('pending')->index();
            $table->string('order_status', 30)->default('pending')->index();
            $table->string('payment_status', 30)->default('unpaid')->index();

            $table->string('ghn_code', 100)->nullable()->index();
            $table->string('payment_method', 30)->default('cod');

            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'order_status', 'payment_status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->unsignedBigInteger('product_id')->index(); 
            $table->unsignedBigInteger('variant_id')->index(); 
            $table->string('product_name');
            $table->json('variant_attributes');
            $table->string('sku', 100)->index();
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
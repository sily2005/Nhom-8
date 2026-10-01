<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('payment_method', ['cod', 'vnpay', 'momo', 'bank_transfer'])->default('cod');
            $table->string('transaction_id')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->string('gateway', 30)->index();
            $table->string('transaction_code', 100)->nullable()->index();
            $table->string('response_code', 20)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // Người gửi
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            // Người nhận
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            // Nội dung tin nhắn
            $table->text('content');
            // Trạng thái đã đọc
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Indexes for fast retrieval
            $table->index(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

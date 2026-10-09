<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sender_id');
                $table->unsignedBigInteger('receiver_id');
                $table->text('content');
                $table->string('sender_type')->default('CUSTOMER'); // 'CUSTOMER', 'ADMIN', 'AI', 'user', 'admin', 'ai'
                $table->text('attachment_url')->nullable();
                $table->string('attachment_type')->nullable(); // 'image', 'file'
                $table->string('attachment_name')->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->index(['sender_id', 'receiver_id', 'is_read']);
                $table->index(['receiver_id', 'is_read']);
                $table->index(['sender_id', 'created_at']);
                $table->index(['receiver_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

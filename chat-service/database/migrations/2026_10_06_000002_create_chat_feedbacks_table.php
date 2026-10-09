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
        if (!Schema::hasTable('chat_feedbacks')) {
            Schema::create('chat_feedbacks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('message_id')->nullable()->index();
                $table->tinyInteger('rating')->default(5); // 1 to 5 stars
                $table->string('feedback_type')->default('ai'); // 'ai' or 'admin'
                $table->text('comment')->nullable();
                $table->json('tags')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_feedbacks');
    }
};

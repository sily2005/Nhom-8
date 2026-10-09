<?php

namespace Database\Seeders;

use App\Models\Message;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tin nhắn mẫu chào mừng khởi tạo cho Chat Service
        Message::updateOrCreate(
            ['id' => 1],
            [
                'sender_id' => 1,
                'receiver_id' => 2,
                'content' => 'Chào mừng bạn đến với STRIKER! Mình có thể giúp gì cho bạn hôm nay? ⚽',
                'sender_type' => 'AI',
                'is_read' => true,
            ]
        );
    }
}



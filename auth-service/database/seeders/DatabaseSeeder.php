<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin
        $admin = User::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Quản Trị Viên Striker',
                'email' => 'admin@striker.vn',
                'password' => Hash::make('password'),
                'phone' => '0909999999',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // 2. Customer 1 - Nguyễn Văn An
        $user2 = User::updateOrCreate(
            ['id' => 2],
            [
                'name' => 'Nguyễn Văn An',
                'email' => 'customer@striker.vn',
                'password' => Hash::make('password'),
                'phone' => '0977777777',
                'role' => 'customer',
                'is_active' => true,
            ]
        );

        // 3. Customer 2 - Trần Minh Hoàng
        $user3 = User::updateOrCreate(
            ['id' => 3],
            [
                'name' => 'Trần Minh Hoàng',
                'email' => 'hoang.tran@gmail.com',
                'password' => Hash::make('password'),
                'phone' => '0988776655',
                'role' => 'customer',
                'is_active' => true,
            ]
        );

        // 4. Customer 3 - Lê Quốc Bảo
        $user4 = User::updateOrCreate(
            ['id' => 4],
            [
                'name' => 'Lê Quốc Bảo',
                'email' => 'baole.striker@gmail.com',
                'password' => Hash::make('password'),
                'phone' => '0912345678',
                'role' => 'customer',
                'is_active' => true,
            ]
        );

        // 5. Customer 4 - Phạm Thu Hương
        $user5 = User::updateOrCreate(
            ['id' => 5],
            [
                'name' => 'Phạm Thu Hương',
                'email' => 'huong.pham@gmail.com',
                'password' => Hash::make('password'),
                'phone' => '0903332211',
                'role' => 'customer',
                'is_active' => true,
            ]
        );

        // 6. Tạo tin nhắn mẫu
        Message::truncate();

        Message::create([
            'sender_id' => $user2->id,
            'receiver_id' => $admin->id,
            'content' => 'Chào shop, đôi giày Nike Phantom GX Elite có sẵn size 41 không ạ?',
            'is_read' => true,
            'created_at' => now()->subHours(5),
        ]);

        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $user2->id,
            'content' => 'Dạ chào bạn, size 41 bên shop đang sẵn hàng nhé! Bạn có thể đặt trực tiếp trên web ạ.',
            'is_read' => true,
            'created_at' => now()->subHours(4),
        ]);

        Message::create([
            'sender_id' => $user4->id,
            'receiver_id' => $admin->id,
            'content' => 'Shop ơi, đơn hàng STR-260903-003 mình vừa thanh toán MoMo đã xác nhận chưa?',
            'is_read' => false,
            'created_at' => now()->subHours(1),
        ]);
    }
}

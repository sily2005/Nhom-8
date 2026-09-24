<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tài khoản Admin chuẩn
        $admin = User::updateOrCreate(
            ['email' => 'admin@striker.vn'],
            [
                'name' => 'Quản Trị Viên Striker',
                'phone_number' => '0988888888',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 2. Tài khoản Khách hàng 1 (Customer VIP)
        $customer1 = User::updateOrCreate(
            ['email' => 'customer@striker.vn'],
            [
                'name' => 'Nguyễn Văn An',
                'phone_number' => '0977777777',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Address::updateOrCreate(
            [
                'user_id' => $customer1->id,
                'phone' => '0977777777',
                'street_address' => 'Số 123 Đường Cầu Giấy',
            ],
            [
                'recipient_name' => 'Nguyễn Văn An',
                'province' => 'Hà Nội',
                'district' => 'Quận Cầu Giấy',
                'ward' => 'Phường Dịch Vọng Hậu',
                'is_default' => true,
            ]
        );

        Address::updateOrCreate(
            [
                'user_id' => $customer1->id,
                'phone' => '0977777777',
                'street_address' => 'Tầng 18 Tòa nhà Landmark 81, 720A Điện Biên Phủ',
            ],
            [
                'recipient_name' => 'Nguyễn Văn An (Văn phòng)',
                'province' => 'Hồ Chí Minh',
                'district' => 'Quận Bình Thạnh',
                'ward' => 'Phường 22',
                'is_default' => false,
            ]
        );

        // 3. Khách hàng 2: Trần Minh Hoàng
        $customer2 = User::updateOrCreate(
            ['email' => 'hoang.tran@gmail.com'],
            [
                'name' => 'Trần Minh Hoàng',
                'phone_number' => '0988776655',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'avatar' => 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=300&q=80',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Address::updateOrCreate(
            [
                'user_id' => $customer2->id,
                'phone' => '0988776655',
                'street_address' => 'Tòa Landmark 81, 720A Điện Biên Phủ',
            ],
            [
                'recipient_name' => 'Trần Minh Hoàng',
                'province' => 'Hồ Chí Minh',
                'district' => 'Quận Bình Thạnh',
                'ward' => 'Phường 22',
                'is_default' => true,
            ]
        );

        // 4. Khách hàng 3: Lê Quốc Bảo
        $customer3 = User::updateOrCreate(
            ['email' => 'baole.striker@gmail.com'],
            [
                'name' => 'Lê Quốc Bảo',
                'phone_number' => '0912345678',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Address::updateOrCreate(
            [
                'user_id' => $customer3->id,
                'phone' => '0912345678',
                'street_address' => 'Số 45 Lê Lợi, Phường Bến Nghé',
            ],
            [
                'recipient_name' => 'Lê Quốc Bảo',
                'province' => 'Hồ Chí Minh',
                'district' => 'Quận 1',
                'ward' => 'Phường Bến Nghé',
                'is_default' => true,
            ]
        );

        // 5. Khách hàng 4: Phạm Thu Hương
        $customer4 = User::updateOrCreate(
            ['email' => 'huong.pham@gmail.com'],
            [
                'name' => 'Phạm Thu Hương',
                'phone_number' => '0903332211',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Address::updateOrCreate(
            [
                'user_id' => $customer4->id,
                'phone' => '0903332211',
                'street_address' => '128 Hai Bà Trưng, Phường Đa Kao',
            ],
            [
                'recipient_name' => 'Phạm Thu Hương',
                'province' => 'Hồ Chí Minh',
                'district' => 'Quận 1',
                'ward' => 'Phường Đa Kao',
                'is_default' => true,
            ]
        );
    }
}
<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Order 1: MoMo completed
        $p1 = Payment::updateOrCreate(
            ['order_id' => 1],
            [
                'user_id' => 2,
                'payment_method' => 'momo',
                'transaction_id' => 'MOMO_STR_1_260901',
                'amount' => 4220000,
                'status' => 'completed',
                'paid_at' => now()->subDays(5),
            ]
        );
        PaymentTransaction::updateOrCreate(
            ['payment_id' => $p1->id],
            [
                'gateway' => 'momo',
                'transaction_code' => 'MOMO_TX_88101',
                'response_code' => '0',
                'amount' => 4220000,
                'status' => 'completed',
                'raw_payload' => ['message' => 'Giao dịch thành công', 'resultCode' => 0],
            ]
        );

        // Order 2: COD pending
        $p2 = Payment::updateOrCreate(
            ['order_id' => 2],
            [
                'user_id' => 3,
                'payment_method' => 'cod',
                'transaction_id' => null,
                'amount' => 3920000,
                'status' => 'pending',
                'paid_at' => null,
            ]
        );
        PaymentTransaction::updateOrCreate(
            ['payment_id' => $p2->id],
            [
                'gateway' => 'cod',
                'transaction_code' => 'COD_STR_2',
                'response_code' => '0',
                'amount' => 3920000,
                'status' => 'pending',
                'raw_payload' => ['message' => 'Thanh toán COD khi nhận hàng'],
            ]
        );

        // Order 3: MoMo completed
        $p3 = Payment::updateOrCreate(
            ['order_id' => 3],
            [
                'user_id' => 4,
                'payment_method' => 'momo',
                'transaction_id' => 'MOMO_STR_3_260903',
                'amount' => 1220000,
                'status' => 'completed',
                'paid_at' => now()->subHours(4),
            ]
        );
        PaymentTransaction::updateOrCreate(
            ['payment_id' => $p3->id],
            [
                'gateway' => 'momo',
                'transaction_code' => 'MOMO_TX_99303',
                'response_code' => '0',
                'amount' => 1220000,
                'status' => 'completed',
                'raw_payload' => ['message' => 'Giao dịch thành công', 'resultCode' => 0],
            ]
        );

        // Order 4: MoMo completed
        $p4 = Payment::updateOrCreate(
            ['order_id' => 4],
            [
                'user_id' => 5,
                'payment_method' => 'momo',
                'transaction_id' => 'MOMO_STR_4_260904',
                'amount' => 3150000,
                'status' => 'completed',
                'paid_at' => now()->subDays(8),
            ]
        );
        PaymentTransaction::updateOrCreate(
            ['payment_id' => $p4->id],
            [
                'gateway' => 'momo',
                'transaction_code' => 'MOMO_TX_63910',
                'response_code' => '0',
                'amount' => 3150000,
                'status' => 'completed',
                'raw_payload' => ['message' => 'Giao dịch thành công', 'resultCode' => 0],
            ]
        );
    }
}


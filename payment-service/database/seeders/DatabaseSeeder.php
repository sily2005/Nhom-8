<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        PaymentSetting::updateOrCreate(
            ['is_active' => true],
            [
                'bank_code' => 'MB',
                'bank_name' => 'MBBank (Ngân hàng Quân Đội)',
                'account_number' => '0977777777',
                'account_name' => 'STRIKER SPORT PRO',
                'syntax_prefix' => 'STR',
                'template' => 'compact2',
            ]
        );
    }
}

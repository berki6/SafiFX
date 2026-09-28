<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Seed the 5 MVP corridors (docs/SAFIFX.md §2).
     *
     * Uses `firstOrCreate` keyed on `code`: a country is seeded once, and
     * re-running this seeder (e.g. because it runs on every deploy) never
     * overwrites receiving-account details an admin has since edited.
     */
    public function run(): void
    {
        $countries = [
            [
                'code' => 'KE',
                'name' => 'Kenya',
                'currency_code' => 'KES',
                'flag_emoji' => '🇰🇪',
                'receiving_network' => 'M-Pesa Paybill',
                'receiving_number' => '522522',
                'receiving_account_name' => 'SafiFX Kenya Ltd',
                'is_active' => true,
            ],
            [
                'code' => 'UG',
                'name' => 'Uganda',
                'currency_code' => 'UGX',
                'flag_emoji' => '🇺🇬',
                'receiving_network' => 'MTN / Airtel Merchant',
                'receiving_number' => '+256 770 123456',
                'receiving_account_name' => 'SafiFX Uganda',
                'is_active' => true,
            ],
            [
                'code' => 'TZ',
                'name' => 'Tanzania',
                'currency_code' => 'TZS',
                'flag_emoji' => '🇹🇿',
                'receiving_network' => 'Vodacom M-Pesa',
                'receiving_number' => '+255 750 987654',
                'receiving_account_name' => 'SafiFX Tanzania',
                'is_active' => true,
            ],
            [
                'code' => 'RW',
                'name' => 'Rwanda',
                'currency_code' => 'RWF',
                'flag_emoji' => '🇷🇼',
                'receiving_network' => 'MTN MoMo Rwanda',
                'receiving_number' => '+250 788 112233',
                'receiving_account_name' => 'SafiFX Rwanda',
                'is_active' => true,
            ],
            [
                'code' => 'ET',
                'name' => 'Ethiopia',
                'currency_code' => 'ETB',
                'flag_emoji' => '🇪🇹',
                'receiving_network' => 'Telebirr',
                'receiving_number' => '+251 911 000000',
                'receiving_account_name' => 'SafiFX Ethiopia',
                'is_active' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::query()->firstOrCreate(
                ['code' => $country['code']],
                $country,
            );
        }
    }
}

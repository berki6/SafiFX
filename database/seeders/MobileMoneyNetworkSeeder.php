<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\MobileMoneyNetwork;
use Illuminate\Database\Seeder;

class MobileMoneyNetworkSeeder extends Seeder
{
    /**
     * Seed the recipient mobile-money networks available per destination country.
     *
     * Uses `firstOrCreate` keyed on (country_id, name): a network is seeded once,
     * and re-running this seeder never overwrites an admin's `is_active` change.
     */
    public function run(): void
    {
        $networksByCountryCode = [
            'KE' => ['M-PESA', 'Airtel Money'],
            'UG' => ['MTN Mobile Money', 'Airtel Money'],
            'TZ' => ['Vodacom M-Pesa', 'Tigo Pesa', 'Airtel Money'],
            'RW' => ['MTN MoMo', 'Airtel Money'],
            'ET' => ['Telebirr', 'M-Pesa'],
        ];

        foreach ($networksByCountryCode as $countryCode => $networks) {
            $country = Country::query()->where('code', $countryCode)->first();

            if (! $country) {
                continue;
            }

            foreach ($networks as $name) {
                MobileMoneyNetwork::query()->firstOrCreate(
                    ['country_id' => $country->id, 'name' => $name],
                    ['is_active' => true],
                );
            }
        }
    }
}

<?php

use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\MobileMoneyNetwork;
use Database\Seeders\CountrySeeder;
use Database\Seeders\ExchangeRateSeeder;
use Database\Seeders\MobileMoneyNetworkSeeder;

test('country seeder creates the 5 mvp corridors', function () {
    $this->seed(CountrySeeder::class);

    expect(Country::count())->toBe(5);
    expect(Country::where('code', 'ET')->exists())->toBeTrue();
    expect(Country::where('currency_code', 'ETB')->exists())->toBeTrue();
});

test('re-running the country seeder does not duplicate rows or overwrite admin edits', function () {
    $this->seed(CountrySeeder::class);

    $kenya = Country::where('code', 'KE')->firstOrFail();
    $kenya->update(['receiving_number' => '999999']);

    $this->seed(CountrySeeder::class);

    expect(Country::count())->toBe(5);
    expect($kenya->fresh()->receiving_number)->toBe('999999');
});

test('mobile money network seeder is idempotent and admin edits survive re-seeding', function () {
    $this->seed(CountrySeeder::class);
    $this->seed(MobileMoneyNetworkSeeder::class);

    $countBeforeRerun = MobileMoneyNetwork::count();
    expect($countBeforeRerun)->toBeGreaterThan(0);

    $network = MobileMoneyNetwork::first();
    $network->update(['is_active' => false]);

    $this->seed(MobileMoneyNetworkSeeder::class);

    expect(MobileMoneyNetwork::count())->toBe($countBeforeRerun);
    expect($network->fresh()->is_active)->toBeFalse();
});

test('exchange rate seeder is idempotent and admin-edited rates survive re-seeding', function () {
    $this->seed(ExchangeRateSeeder::class);

    $countBeforeRerun = ExchangeRate::count();
    $rate = ExchangeRate::where('from_currency', 'KES')->where('to_currency', 'UGX')->firstOrFail();
    $rate->update(['rate' => 30.5]);

    $this->seed(ExchangeRateSeeder::class);

    expect(ExchangeRate::count())->toBe($countBeforeRerun);
    expect($rate->fresh()->rate)->toEqualWithDelta(30.5, 0.0001);
});

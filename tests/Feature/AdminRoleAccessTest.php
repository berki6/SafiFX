<?php

use App\Enums\UserRole;
use App\Models\LiquidityBalance;
use App\Models\User;

test('a super admin can access country and exchange rate settings', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]);

    $this->actingAs($admin)->get('/admin/countries')->assertOk();
    $this->actingAs($admin)->get('/admin/exchange-rates')->assertOk();
});

test('an operator is forbidden from country and exchange rate settings', function () {
    $operator = User::factory()->create(['is_admin' => true, 'role' => UserRole::Operator]);

    $this->actingAs($operator)->get('/admin/countries')->assertForbidden();
    $this->actingAs($operator)->get('/admin/exchange-rates')->assertForbidden();
});

test('an operator can still view transactions and liquidity balances', function () {
    $operator = User::factory()->create(['is_admin' => true, 'role' => UserRole::Operator]);

    $this->actingAs($operator)->get('/admin/transactions')->assertOk();
    $this->actingAs($operator)->get('/admin/liquidity-balances')->assertOk();
});

test('an operator can view a liquidity balance but not edit it', function () {
    $balance = LiquidityBalance::factory()->create();
    $operator = User::factory()->create(['is_admin' => true, 'role' => UserRole::Operator]);

    $this->actingAs($operator)
        ->get("/admin/liquidity-balances/{$balance->id}/edit")
        ->assertForbidden();
});

test('a super admin can edit a liquidity balance', function () {
    $balance = LiquidityBalance::factory()->create();
    $admin = User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]);

    $this->actingAs($admin)
        ->get("/admin/liquidity-balances/{$balance->id}/edit")
        ->assertOk();
});

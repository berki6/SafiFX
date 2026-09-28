<?php

namespace App\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $currency_code
 * @property string|null $flag_emoji
 * @property string $receiving_network
 * @property string $receiving_number
 * @property string $receiving_account_name
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'code',
    'currency_code',
    'flag_emoji',
    'receiving_network',
    'receiving_number',
    'receiving_account_name',
    'is_active',
])]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    /**
     * The mobile-money networks recipients can be paid out through in this country.
     *
     * @return HasMany<MobileMoneyNetwork, $this>
     */
    public function mobileMoneyNetworks(): HasMany
    {
        return $this->hasMany(MobileMoneyNetwork::class);
    }

    /**
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

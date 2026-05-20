<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_code',
        'province_code',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /**
     * Rates for this zone, ordered by sort_order ascending.
     */
    public function rates(): HasMany
    {
        return $this->hasMany(TaxZoneRate::class)->orderBy('sort_order');
    }

    /**
     * Scope to only active zones.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope to zones that match a given country and optionally a province.
     *
     * Returns zones where:
     * - country_code matches the given country AND
     * - province_code matches the given province OR province_code is NULL (country catch-all)
     *
     * Results ordered by priority DESC so the most specific (highest priority) zone is first.
     */
    public function scopeForAddress(Builder $query, string $country, ?string $province): void
    {
        $query->where('country_code', $country)
            ->where(function (Builder $q) use ($province): void {
                $q->where('province_code', $province)
                    ->orWhereNull('province_code');
            })
            ->orderBy('priority', 'desc');
    }
}

<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxZoneRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_zone_id',
        'name',
        'name_fr',
        'rate',
        'is_compound',
        'is_shipping_taxable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_compound' => 'boolean',
            'is_shipping_taxable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(TaxZone::class, 'tax_zone_id');
    }
}

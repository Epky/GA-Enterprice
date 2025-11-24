<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProductSpecification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'spec_key',
        'spec_value',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Get the product that owns the specification.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to order specifications by display order.
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('display_order')->orderBy('spec_key');
    }

    /**
     * Scope a query to search specifications by key or value.
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search) {
            $query->where('spec_key', 'ILIKE', "%{$search}%")
                  ->orWhere('spec_value', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Get the formatted specification key (human-readable).
     */
    public function getFormattedKeyAttribute(): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $this->spec_key));
    }

    /**
     * Get the formatted specification value (with proper formatting).
     */
    public function getFormattedValueAttribute(): string
    {
        // Handle different types of values
        $value = $this->spec_value;

        // If it's a numeric value with units, format it nicely
        if (preg_match('/^(\d+(?:\.\d+)?)\s*([a-zA-Z%]+)$/', $value, $matches)) {
            return $matches[1] . ' ' . $matches[2];
        }

        // If it's a boolean-like value, format it
        if (in_array(strtolower($value), ['yes', 'no', 'true', 'false', '1', '0'])) {
            return in_array(strtolower($value), ['yes', 'true', '1']) ? 'Yes' : 'No';
        }

        return $value;
    }

    /**
     * Check if this specification is a key specification (commonly displayed).
     */
    public function getIsKeySpecAttribute(): bool
    {
        $keySpecs = [
            'brand',
            'color',
            'size',
            'weight',
            'volume',
            'ingredients',
            'skin_type',
            'hair_type',
            'coverage',
            'finish',
            'spf',
            'cruelty_free',
            'vegan',
            'organic',
            'paraben_free',
        ];

        return in_array(strtolower($this->spec_key), $keySpecs);
    }
}
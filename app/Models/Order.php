<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    use HasFactory;

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear analytics cache when an order is created
        static::created(function ($order) {
            static::clearAnalyticsCache();
        });

        // Clear analytics cache when an order is updated
        static::updated(function ($order) {
            static::clearAnalyticsCache();
        });
    }

    /**
     * Clear all analytics caches.
     */
    protected static function clearAnalyticsCache(): void
    {
        // Clear all analytics-related cache keys
        // This is a simple implementation using cache tags pattern
        $cacheKeys = [
            'analytics:revenue:*',
            'analytics:order_metrics:*',
            'analytics:top_products_*',
            'analytics:sales_by_category:*',
            'analytics:sales_by_brand:*',
            'analytics:payment_distribution:*',
            'analytics:channel_comparison:*',
            'analytics:sales_trend:*',
            'analytics:customer_metrics:*',
            'analytics:profit_metrics:*',
        ];

        // Since we can't use wildcard deletion in basic cache,
        // we'll use a more targeted approach by clearing common periods
        $periods = ['today', 'week', 'month', 'year'];
        $now = \Carbon\Carbon::now();

        foreach ($periods as $period) {
            \Illuminate\Support\Facades\Cache::forget("analytics:revenue:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:order_metrics:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:top_products_10:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:sales_by_category:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:sales_by_brand:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:payment_distribution:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:channel_comparison:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:sales_trend:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:customer_metrics:{$period}:{$now->format('Y-m-d')}");
            \Illuminate\Support\Facades\Cache::forget("analytics:profit_metrics:{$period}:{$now->format('Y-m-d')}");
        }
    }

    protected $fillable = [
        'order_number',
        'user_id',
        'order_type',
        'order_status',
        'payment_status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'shipping_address_id',
        'shipping_method',
        'tracking_number',
        'notes',
        'internal_notes',
        'ip_address',
        'user_agent',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the user (staff) who created this order.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the order items.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payment for this order.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Scope for walk-in orders.
     */
    public function scopeWalkIn(Builder $query): void
    {
        $query->where('order_type', 'walk_in');
    }

    /**
     * Scope for pending orders.
     */
    public function scopePending(Builder $query): void
    {
        $query->where('order_status', 'pending');
    }

    /**
     * Scope for completed orders.
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('order_status', 'completed');
    }

    /**
     * Scope for cancelled orders.
     */
    public function scopeCancelled(Builder $query): void
    {
        $query->where('order_status', 'cancelled');
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->order_status === 'pending';
    }

    /**
     * Check if order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->order_status === 'completed';
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->order_status === 'cancelled';
    }
}

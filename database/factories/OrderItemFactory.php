<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $product->base_price;
        $discountAmount = 0;
        $taxAmount = 0;
        $totalPrice = ($unitPrice * $quantity) - $discountAmount + $taxAmount;

        return [
            'order_id' => Order::factory(),
            'product_id' => $product->id,
            'variant_id' => null,
            'product_name' => $product->name,
            'variant_name' => null,
            'sku' => $product->sku,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_price' => $totalPrice,
        ];
    }
}

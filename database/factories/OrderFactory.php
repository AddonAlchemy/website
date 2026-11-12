<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'ORD-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)),
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'payment_method' => 'card',
            'payment_method_label' => 'Kreditkarte',
            'payment_fee_percent' => 1.5,
            'payment_fee_fixed_cents' => 25,
            'subtotal_cents' => 999,
            'discount_cents' => 0,
            'total_cents' => 999,
            'billing' => ['name' => 'Max Mustermann', 'email' => 'max@example.com'],
        ];
    }
}

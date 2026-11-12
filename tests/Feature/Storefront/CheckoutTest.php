<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Enums\OrderStatus;
use App\Enums\SettlementStatus;
use App\Models\License;
use App\Models\Order;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['marketplace.checkout.auto_capture' => false]);
    }

    public function test_guests_can_fill_the_cart_but_must_log_in_to_order(): void
    {
        $plugin = $this->publishedPlugin(['price_cents' => 599]);

        $this->post(route('cart.store', $plugin))->assertRedirect();
        $this->get(route('cart.show'))->assertOk()->assertSee($plugin->name)->assertSee('5,99 €');
        $this->post(route('checkout.store'), ['terms' => '1'])->assertRedirect(route('login'));
    }

    public function test_paid_orders_stay_pending_until_an_admin_confirms_the_payment(): void
    {
        $customer = User::factory()->create();
        $plugin = $this->publishedPlugin(['price_cents' => 599]);

        $this->actingAs($customer)->post(route('cart.store', $plugin));
        $this->actingAs($customer)
            ->post(route('checkout.store'), ['payment_method' => 'sepa', 'terms' => '1'])
            ->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(599, $order->total_cents);
        $this->assertDatabaseCount('licenses', 0);

        $this->actingAs($customer)->post(route('admin.orders.fulfill', $order))->assertForbidden();
        $this->actingAs($this->admin())->post(route('admin.orders.fulfill', $order))->assertRedirect();

        $order->refresh();
        $license = License::query()->sole();
        $settlement = $order->settlements()->sole();

        $this->assertSame(OrderStatus::Fulfilled, $order->status);
        $this->assertTrue($license->user->is($customer));
        $this->assertMatchesRegularExpression('/^AALC(-[A-Z0-9]{4}){4}$/', $license->key);
        $this->assertNotNull($order->invoice);
        $this->assertSame(1, $plugin->refresh()->sales_count);

        $this->assertSame(SettlementStatus::Pending, $settlement->status);
        $this->assertSame(599, $settlement->gross_cents);
        $this->assertSame(30, $settlement->payment_fee_cents);
        $this->assertSame(199, $settlement->platform_fee_cents);
        $this->assertSame(370, $settlement->net_cents);
    }

    public function test_orders_are_captured_immediately_when_auto_capture_is_enabled(): void
    {
        config(['marketplace.checkout.auto_capture' => true]);

        $customer = User::factory()->create();
        $plugin = $this->publishedPlugin(['price_cents' => 999]);

        $this->actingAs($customer)->post(route('cart.store', $plugin));
        $this->actingAs($customer)->post(route('checkout.store'), ['terms' => '1']);

        $this->assertSame(OrderStatus::Fulfilled, Order::query()->sole()->status);
        $this->assertTrue($customer->hasUsableLicenseFor($plugin));
        $this->assertCount(2, $customer->notifications);
    }

    public function test_sales_reduce_the_price_and_split_the_discount_between_shop_and_vendor(): void
    {
        config(['marketplace.checkout.auto_capture' => true]);

        $customer = User::factory()->create();
        $plugin = $this->publishedPlugin(['price_cents' => 2000]);
        Sale::query()->create(['name' => 'Half Split', 'percent' => 20, 'vendor_share_percent' => 50]);

        $this->actingAs($customer)->post(route('cart.store', $plugin));
        $this->actingAs($customer)->post(route('checkout.store'), ['payment_method' => 'card', 'terms' => '1']);

        $order = Order::query()->sole();
        $settlement = $order->settlements()->sole();

        $this->assertSame(1600, $order->total_cents);
        $this->assertSame(400, $order->discount_cents);
        $this->assertSame(2000, $settlement->gross_cents);
        $this->assertSame(200, $settlement->vendor_discount_cents);
        $this->assertSame(200, $settlement->platform_discount_cents);
        $this->assertSame(49, $settlement->payment_fee_cents);
        $this->assertSame(613, $settlement->platform_fee_cents);
        $this->assertSame(2000 - 200 - 49 - 613, $settlement->net_cents);
    }

    public function test_a_perpetual_license_cannot_be_bought_twice(): void
    {
        $customer = User::factory()->create();
        $plugin = $this->publishedPlugin();
        License::factory()->create(['user_id' => $customer->id, 'plugin_id' => $plugin->id]);

        $this->actingAs($customer)->post(route('cart.store', $plugin));
        $this->actingAs($customer)
            ->from(route('cart.show'))
            ->post(route('checkout.store'), ['terms' => '1'])
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_annual_licenses_expire_and_are_extended_on_renewal(): void
    {
        config(['marketplace.checkout.auto_capture' => true]);

        $customer = User::factory()->create();
        $plugin = $this->publishedPlugin(['license_model' => 'annual']);

        foreach (range(1, 2) as $ignored) {
            $this->actingAs($customer)->post(route('cart.store', $plugin));
            $this->actingAs($customer)->post(route('checkout.store'), ['terms' => '1']);
        }

        $license = License::query()->sole();

        $this->assertEqualsWithDelta(now()->addYears(2)->timestamp, $license->expires_at->timestamp, 5);
    }

    public function test_pending_orders_can_be_cancelled_by_admins(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.orders.cancel', $order))->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }
}

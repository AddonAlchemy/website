<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_persisted_and_applied_to_the_storefront(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->payload(['brand_first' => 'PLUGIN', 'brand_second' => 'FORGE', 'accent_color' => '#3366FF']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $settings = app(Settings::class);

        $this->assertSame('PLUGIN', $settings->get('brand_first'));
        $this->assertSame(750, $settings->minPriceCents());
        $this->assertSame(30.5, $settings->platformFeePercent());
        $this->assertSame(['id' => 'paypal', 'label' => 'PayPal', 'percent' => 2.49, 'fixed_cents' => 35], $settings->get('payment_methods')[0]);

        $this->get(route('home'))->assertSee('PLUGIN')->assertSee('--color-brand: #3366FF', false);
    }

    public function test_invalid_accent_colors_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->payload(['accent_color' => 'red;}</style><script>']))
            ->assertSessionHasErrors('accent_color');
    }

    public function test_maintenance_mode_locks_out_everyone_except_admins(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update'), $this->payload(['maintenance' => '1', 'maintenance_message' => 'Gleich zurück.']));
        $this->post(route('logout'));

        $this->get(route('home'))->assertStatus(503)->assertSee('Gleich zurück.');
        $this->actingAs(User::factory()->create())->get(route('plugins.index'))->assertStatus(503);
        $this->post(route('logout'));
        $this->get(route('login'))->assertOk();
        $this->actingAs($admin)->get(route('home'))->assertOk();
    }

    public function test_legal_pages_render_admin_provided_markdown(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->payload(['legal_imprint' => "## Impressum\n\nMuster GmbH"]));

        $this->get(route('legal.show', 'impressum'))->assertOk()->assertSee('Muster GmbH');
        $this->get(route('legal.show', 'agb'))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'brand_first' => 'ADDON',
            'brand_second' => 'ALCHEMY',
            'tagline' => 'Marktplatz',
            'accent_color' => '#88C040',
            'currency' => '€',
            'maintenance' => '0',
            'max_ips' => 3,
            'subkey_max_plugins' => 3,
            'subkey_lock_days' => 14,
            'min_price' => '7,50',
            'platform_fee_percent' => '30,5',
            'payment_methods' => [['label' => 'PayPal', 'percent' => '2,49', 'fixed' => '0,35']],
            'cookie_enabled' => '1',
        ];
    }
}

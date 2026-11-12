<?php

declare(strict_types=1);

namespace Tests\Feature\Vendor;

use App\Enums\PluginStatus;
use App\Enums\VersionStatus;
use App\Models\Category;
use App\Models\License;
use App\Models\Platform;
use App\Models\Plugin;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PluginManagementTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->vendor = $this->approvedVendor();
    }

    public function test_customers_are_sent_to_the_application_form(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.apply'));

        $this->actingAs($customer)
            ->post(route('vendor.apply.store'), ['name' => 'Fresh Studio', 'terms' => '1'])
            ->assertRedirect(route('vendor.dashboard'));

        $this->assertTrue($customer->refresh()->isVendor());
        $this->assertFalse($customer->vendor->isApproved());
    }

    public function test_pending_vendors_cannot_create_plugins(): void
    {
        $pending = Vendor::factory()->pending()->create();

        $this->actingAs($pending->user)->get(route('vendor.plugins.create'))->assertRedirect(route('vendor.dashboard'));
        $this->actingAs($pending->user)->post(route('vendor.plugins.store'), $this->pluginPayload())->assertRedirect(route('vendor.dashboard'));

        $this->assertDatabaseCount('plugins', 0);
    }

    public function test_vendors_create_plugins_as_drafts_with_tags_and_icon(): void
    {
        $this->actingAs($this->vendor->user)
            ->post(route('vendor.plugins.store'), $this->pluginPayload([
                'price' => '12,50',
                'tags' => 'Economy, shop, economy',
                'icon' => UploadedFile::fake()->image('icon.png', 256, 256),
            ]))
            ->assertRedirect();

        $plugin = Plugin::query()->sole();

        $this->assertSame(PluginStatus::Draft, $plugin->status);
        $this->assertSame(1250, $plugin->price_cents);
        $this->assertMatchesRegularExpression('/^PRD(-[A-Z0-9]{4}){2}$/', $plugin->public_id);
        $this->assertEqualsCanonicalizing(['Economy', 'shop'], $plugin->tags->pluck('name')->all());
        Storage::disk('public')->assertExists($plugin->icon_path);
    }

    public function test_prices_below_the_minimum_are_rejected(): void
    {
        $this->actingAs($this->vendor->user)
            ->post(route('vendor.plugins.store'), $this->pluginPayload(['price' => '2,00']))
            ->assertSessionHasErrors('price');

        $this->actingAs($this->vendor->user)
            ->post(route('vendor.plugins.store'), $this->pluginPayload(['price' => '0']))
            ->assertSessionHasNoErrors();
    }

    public function test_versions_are_stored_privately_and_wait_for_moderation(): void
    {
        $plugin = Plugin::factory()->draft()->create(['vendor_id' => $this->vendor->id]);
        $platform = Platform::query()->create(['name' => 'Paper', 'slug' => 'paper']);

        $this->actingAs($this->vendor->user)
            ->post(route('vendor.versions.store', $plugin), [
                'version' => '1.0.0',
                'changelog' => '- Initial release',
                'game_versions' => '1.20, 1.21',
                'platforms' => [$platform->id],
                'archive' => $this->zipUpload(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $version = $plugin->versions()->sole();

        $this->assertSame(VersionStatus::Pending, $version->status);
        $this->assertSame(['1.20', '1.21'], $version->game_versions);
        $this->assertSame(64, strlen($version->file_hash));
        $this->assertStringStartsWith('releases/'.$plugin->public_id.'/', $version->file_path);
        Storage::disk('local')->assertExists($version->file_path);
        Storage::disk('public')->assertMissing($version->file_path);
    }

    public function test_malicious_or_invalid_archives_are_rejected(): void
    {
        $plugin = Plugin::factory()->draft()->create(['vendor_id' => $this->vendor->id]);
        $upload = fn (UploadedFile $file) => $this->actingAs($this->vendor->user)
            ->from(route('vendor.plugins.edit', $plugin))
            ->post(route('vendor.versions.store', $plugin), ['version' => '1.0.0', 'archive' => $file]);

        $upload($this->zipUpload(['../../etc/passwd' => 'root']))->assertSessionHas('error');
        $upload($this->zipUpload(['bomb.txt' => str_repeat('0', 12_000_000)]))->assertSessionHas('error');
        $upload(UploadedFile::fake()->createWithContent('release.zip', 'this is not a zip archive'))->assertSessionHas('error');
        $upload(UploadedFile::fake()->create('release.exe', 20))->assertSessionHasErrors('archive');

        $this->assertDatabaseCount('plugin_versions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_duplicate_and_malformed_version_numbers_are_rejected(): void
    {
        $plugin = Plugin::factory()->create(['vendor_id' => $this->vendor->id]);
        $this->releaseFor($plugin, '1.0.0');

        foreach (['1.0.0', 'latest', '1..2'] as $number) {
            $this->actingAs($this->vendor->user)
                ->post(route('vendor.versions.store', $plugin), ['version' => $number, 'archive' => $this->zipUpload()])
                ->assertSessionHasErrors('version');
        }
    }

    public function test_plugins_need_a_version_before_they_can_be_submitted(): void
    {
        $plugin = Plugin::factory()->draft()->create(['vendor_id' => $this->vendor->id]);

        $this->actingAs($this->vendor->user)->post(route('vendor.plugins.submit', $plugin))->assertSessionHas('error');
        $this->assertSame(PluginStatus::Draft, $plugin->refresh()->status);

        $this->releaseFor($plugin)->update(['status' => VersionStatus::Pending]);
        $admin = $this->admin();

        $this->actingAs($this->vendor->user)->post(route('vendor.plugins.submit', $plugin))->assertSessionHas('success');
        $this->assertSame(PluginStatus::Pending, $plugin->refresh()->status);
        $this->assertCount(1, $admin->notifications);
    }

    public function test_vendors_cannot_touch_plugins_of_other_studios(): void
    {
        $foreign = Plugin::factory()->create();
        $release = $this->releaseFor($foreign);

        $this->actingAs($this->vendor->user)->get(route('vendor.plugins.edit', $foreign))->assertForbidden();
        $this->actingAs($this->vendor->user)->put(route('vendor.plugins.update', $foreign), $this->pluginPayload())->assertForbidden();
        $this->actingAs($this->vendor->user)->delete(route('vendor.versions.destroy', $release))->assertForbidden();
        $this->actingAs($this->vendor->user)->delete(route('vendor.plugins.destroy', $foreign))->assertForbidden();
    }

    public function test_plugins_with_customers_are_archived_instead_of_deleted(): void
    {
        $sold = $this->publishedPlugin(['vendor_id' => $this->vendor->id]);
        License::factory()->create(['plugin_id' => $sold->id]);
        $unsold = $this->publishedPlugin(['vendor_id' => $this->vendor->id]);
        $archivePath = $unsold->latestRelease->file_path;

        $this->actingAs($this->vendor->user)->delete(route('vendor.plugins.destroy', $sold));
        $this->actingAs($this->vendor->user)->delete(route('vendor.plugins.destroy', $unsold));

        $this->assertSame(PluginStatus::Archived, $sold->refresh()->status);
        $this->assertModelMissing($unsold);
        Storage::disk('local')->assertMissing($archivePath);
    }

    public function test_license_codes_can_be_generated_redeemed_and_revoked(): void
    {
        $plugin = $this->publishedPlugin(['vendor_id' => $this->vendor->id]);
        $customer = User::factory()->create();

        $this->actingAs($this->vendor->user)
            ->post(route('vendor.codes.store', $plugin), ['quantity' => 3, 'note' => 'Promo'])
            ->assertSessionHas('success');

        $code = $plugin->licenseCodes()->firstOrFail();
        $this->assertMatchesRegularExpression('/^LIC(-[A-Z0-9]{4}){3}$/', $code->code);

        $this->actingAs($customer)->post(route('account.licenses.redeem'), ['code' => strtolower($code->code)])->assertSessionHas('success');
        $this->assertTrue($customer->hasUsableLicenseFor($plugin));

        $this->actingAs(User::factory()->create())
            ->post(route('account.licenses.redeem'), ['code' => $code->code])
            ->assertSessionHas('error');

        $this->actingAs($this->vendor->user)->patch(route('vendor.codes.revoke', $code))->assertSessionHas('success');
        $this->assertFalse($customer->hasUsableLicenseFor($plugin));
    }

    public function test_dashboard_and_payout_pages_render_for_vendors(): void
    {
        $this->publishedPlugin(['vendor_id' => $this->vendor->id]);

        foreach (['vendor.dashboard', 'vendor.plugins.index', 'vendor.payouts', 'vendor.settlements.index', 'vendor.tickets.index', 'vendor.profile.edit'] as $route) {
            $this->actingAs($this->vendor->user)->get(route($route))->assertOk();
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function pluginPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Vault Market',
            'summary' => 'Auction house and player shops for your server.',
            'description' => "## Features\n\n- Auctions",
            'category_id' => Category::factory()->create()->id,
            'price' => '9,99',
            'license_model' => 'perpetual',
        ];
    }
}

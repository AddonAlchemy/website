<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\PluginStatus;
use App\Enums\SettlementStatus;
use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Enums\VersionStatus;
use App\Models\License;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_admin_area_is_restricted_to_administrators(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->approvedVendor()->user)->get(route('admin.moderation.index'))->assertForbidden();
    }

    public function test_every_admin_page_renders(): void
    {
        $admin = $this->admin();
        $plugin = $this->publishedPlugin();
        License::factory()->create(['plugin_id' => $plugin->id])->activations()->create([
            'instance_id' => 'INST-AAAAA-BBBBB', 'ip' => '203.0.113.9', 'last_seen_at' => now(),
        ]);
        Order::factory()->create();

        $routes = [
            'admin.dashboard', 'admin.moderation.index', 'admin.plugins.index', 'admin.vendors.index',
            'admin.users.index', 'admin.orders.index', 'admin.settlements.index', 'admin.sales.index',
            'admin.announcements.index', 'admin.categories.index', 'admin.activations.index',
            'admin.tickets.index', 'admin.settings.edit',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.users.show', $admin))->assertOk();
    }

    public function test_approving_a_plugin_publishes_it_together_with_its_pending_versions(): void
    {
        $admin = $this->admin();
        $plugin = Plugin::factory()->pending()->create();
        $platform = Platform::query()->create(['name' => 'Paper', 'slug' => 'paper']);
        $version = PluginVersion::factory()->pending()->create(['plugin_id' => $plugin->id, 'version' => '1.0.0']);
        $version->platforms()->attach($platform);

        $this->actingAs($admin)->get(route('admin.moderation.index'))->assertOk()->assertSee($plugin->name);
        $this->get(route('plugins.index'))->assertDontSee($plugin->name);

        $this->actingAs($admin)->post(route('admin.moderation.plugins.approve', $plugin))->assertSessionHas('success');

        $plugin->refresh();

        $this->assertSame(PluginStatus::Approved, $plugin->status);
        $this->assertNotNull($plugin->published_at);
        $this->assertSame('1.0.0', $plugin->latest_version);
        $this->assertSame(VersionStatus::Approved, $version->refresh()->status);
        $this->assertTrue($version->reviewer->is($admin));
        $this->assertSame(['Paper'], $plugin->platforms->pluck('name')->all());
        $this->assertCount(1, $plugin->vendor->user->notifications);
    }

    public function test_rejections_require_a_reason_and_inform_the_vendor(): void
    {
        $admin = $this->admin();
        $plugin = Plugin::factory()->pending()->create();

        $this->actingAs($admin)->post(route('admin.moderation.plugins.reject', $plugin))->assertSessionHasErrors('reason');

        $this->actingAs($admin)
            ->post(route('admin.moderation.plugins.reject', $plugin), ['reason' => 'Bitte Beschreibung ergänzen.'])
            ->assertSessionHas('success');

        $this->assertSame(PluginStatus::Rejected, $plugin->refresh()->status);
        $this->assertSame('Bitte Beschreibung ergänzen.', $plugin->rejection_reason);
        $this->assertCount(1, $plugin->vendor->user->notifications);
    }

    public function test_new_versions_of_published_plugins_go_live_after_approval(): void
    {
        $admin = $this->admin();
        $plugin = $this->publishedPlugin([], '1.0.0');
        $update = PluginVersion::factory()->pending()->create(['plugin_id' => $plugin->id, 'version' => '1.1.0']);

        $this->get(route('plugins.show', $plugin))->assertDontSee('v1.1.0');

        $this->actingAs($admin)->post(route('admin.moderation.versions.approve', $update))->assertSessionHas('success');
        $this->assertSame('1.1.0', $plugin->refresh()->latest_version);

        $rejected = PluginVersion::factory()->pending()->create(['plugin_id' => $plugin->id, 'version' => '1.2.0']);
        $this->actingAs($admin)
            ->post(route('admin.moderation.versions.reject', $rejected), ['reason' => 'Archiv unvollständig'])
            ->assertSessionHas('success');

        $this->assertSame(VersionStatus::Rejected, $rejected->refresh()->status);
        $this->assertSame('1.1.0', $plugin->refresh()->latest_version);
    }

    public function test_vendor_applications_can_be_approved_and_suspended(): void
    {
        $admin = $this->admin();
        $vendor = Vendor::factory()->pending()->create();

        $this->actingAs($admin)->patch(route('admin.vendors.update', $vendor), ['status' => 'approved'])->assertSessionHas('success');
        $this->assertTrue($vendor->refresh()->isApproved());
        $this->assertNotNull($vendor->approved_at);

        $this->actingAs($admin)->patch(route('admin.vendors.update', $vendor), ['status' => 'suspended', 'reason' => 'Verstoß'])->assertSessionHas('success');
        $this->assertSame(VendorStatus::Suspended, $vendor->refresh()->status);
    }

    public function test_user_management_guards_against_locking_out_admins(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.update', $user), ['role' => 'vendor'])->assertSessionHas('success');
        $this->assertSame(UserRole::Vendor, $user->refresh()->role);

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), ['role' => 'customer'])->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->refresh()->isAdmin());

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($user);
    }

    public function test_licenses_can_be_granted_and_revoked_manually(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $plugin = $this->publishedPlugin();

        $this->actingAs($admin)->post(route('admin.users.licenses.store', $user), ['plugin_id' => $plugin->id])->assertSessionHas('success');
        $license = $user->licenses()->sole();
        $this->assertTrue($license->isUsable());

        $this->actingAs($admin)->delete(route('admin.licenses.destroy', $license))->assertSessionHas('success');
        $this->assertFalse($license->refresh()->isUsable());
    }

    public function test_settlement_status_changes_are_tracked_and_announced(): void
    {
        $admin = $this->admin();
        $vendor = $this->approvedVendor();
        $settlement = Settlement::query()->create([
            'number' => 'TXN-TEST-TEST-TEST',
            'vendor_id' => $vendor->id,
            'order_id' => Order::factory()->create()->id,
            'units' => 1,
            'gross_cents' => 999,
            'net_cents' => 600,
        ]);

        $this->actingAs($admin)->patch(route('admin.settlements.update', $settlement), ['status' => 'available'])->assertSessionHas('success');

        $settlement->refresh();
        $this->assertSame(SettlementStatus::Available, $settlement->status);
        $this->assertNotNull($settlement->released_at);
        $this->assertCount(1, $vendor->user->notifications);

        $this->actingAs($vendor->user)->get(route('vendor.settlements.show', $settlement))->assertOk()->assertSee('TXN-TEST-TEST-TEST');
        $this->actingAs($this->approvedVendor()->user)->get(route('vendor.settlements.show', $settlement))->assertForbidden();
    }

    public function test_featured_flag_sales_announcements_and_categories_can_be_managed(): void
    {
        $admin = $this->admin();
        $plugin = $this->publishedPlugin();

        $this->actingAs($admin)->patch(route('admin.plugins.feature', $plugin));
        $this->assertTrue($plugin->refresh()->is_featured);

        $this->actingAs($admin)->post(route('admin.sales.store'), [
            'name' => 'Sommer', 'percent' => '12,5', 'scope' => 'vendor', 'vendor_id' => $plugin->vendor_id, 'bearer' => 'split', 'vendor_share_percent' => 40,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales', ['name' => 'Sommer', 'percent' => 12.5, 'vendor_share_percent' => 40, 'vendor_id' => $plugin->vendor_id]);

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Wartung', 'body' => 'Heute Nacht.', 'type' => 'sale', 'audience' => 'all',
        ])->assertSessionHasNoErrors();
        $this->get(route('home'))->assertSee('Heute Nacht.');

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Neue Kategorie', 'icon' => 'rocket'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['slug' => 'neue-kategorie']);
    }
}

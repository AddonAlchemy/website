<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\OrderStatus;
use App\Enums\SettlementStatus;
use App\Models\Category;
use App\Models\License;
use App\Models\Order;
use App\Models\Plugin;
use App\Models\Settlement;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    private string $legacyPath;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Category::factory()->create(['slug' => 'administration']);

        $this->legacyPath = sys_get_temp_dir().'/legacy-'.uniqid();
        File::makeDirectory($this->legacyPath.'/data', recursive: true);
        File::makeDirectory($this->legacyPath.'/protected/files', recursive: true);
        File::put($this->legacyPath.'/protected/files/file_abc.zip', 'legacy-archive');
        File::put($this->legacyPath.'/data/db.json', json_encode($this->fixture()));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->legacyPath);

        parent::tearDown();
    }

    public function test_it_imports_accounts_catalog_licenses_and_orders(): void
    {
        $this->artisan('marketplace:import-legacy', ['path' => $this->legacyPath])->assertSuccessful();

        $customer = User::query()->where('email', 'kunde@example.com')->sole();
        $plugin = Plugin::query()->where('public_id', 'PRD-YMUP-XS2A')->sole();
        $license = License::query()->sole();
        $order = Order::query()->sole();

        $this->assertTrue(Hash::check('geheim', $customer->password));
        $this->assertTrue(User::query()->where('email', 'admin@example.com')->sole()->isAdmin());
        $this->assertTrue($plugin->vendor->isApproved());
        $this->assertSame(599, $plugin->price_cents);
        $this->assertSame('1.0.0', $plugin->latest_version);
        $this->assertStringContainsString('**Staff** Manager', $plugin->description);
        Storage::disk('local')->assertExists($plugin->latestRelease->file_path);

        $this->assertSame('AALC-8BKE-CXXN-3LN6-DUHT', $license->key);
        $this->assertSame(1, $license->activations()->count());
        $this->assertSame(OrderStatus::Fulfilled, $order->status);
        $this->assertNotNull($order->invoice);
        $this->assertSame(SettlementStatus::Available, Settlement::query()->sole()->status);
        $this->assertSame(370, Settlement::query()->sole()->net_cents);
        $this->assertSame(3, app(Settings::class)->maxIps());

        $this->postJson('/api/v1/activate', ['key' => $license->key, 'product_id' => 'PRD-YMUP-XS2A'])->assertOk();
    }

    public function test_it_can_be_run_twice_without_creating_duplicates(): void
    {
        $this->artisan('marketplace:import-legacy', ['path' => $this->legacyPath])->assertSuccessful();
        $this->artisan('marketplace:import-legacy', ['path' => $this->legacyPath])->assertSuccessful();

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('plugins', 1);
        $this->assertDatabaseCount('licenses', 1);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_it_fails_gracefully_without_a_database_file(): void
    {
        $this->artisan('marketplace:import-legacy', ['path' => '/nonexistent'])->assertFailed();
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(): array
    {
        return [
            'kunden' => [[
                'email' => 'kunde@example.com',
                'password' => password_hash('geheim', PASSWORD_BCRYPT, ['cost' => 4]),
                'username' => 'kunde',
                'license_key' => 'AALC-8BKE-CXXN-3LN6-DUHT',
                'products' => ['PRD-YMUP-XS2A'],
                'license' => [
                    'ips' => ['203.0.113.9'],
                    'instances' => [['instance_id' => 'INST-AAAAA-BBBBB', 'product_id' => 'PRD-YMUP-XS2A', 'ip' => '203.0.113.9', 'name' => 'Lobby', 'last_seen' => 1783981700]],
                    'subusers' => [],
                ],
            ]],
            'developer' => [[
                'email' => 'studio@example.com',
                'password' => password_hash('geheim', PASSWORD_BCRYPT, ['cost' => 4]),
                'studio' => 'Media Tech Wilms',
                'approved' => true,
            ]],
            'admins' => [['email' => 'admin@example.com', 'password' => password_hash('geheim', PASSWORD_BCRYPT, ['cost' => 4])]],
            'products' => [[
                'product_id' => 'PRD-YMUP-XS2A',
                'name' => 'Staff Manager',
                'type' => 'js',
                'price' => 5.99,
                'vendor' => 'Media Tech Wilms',
                'created_at' => 1783981512,
                'description_html' => '<p><strong>Staff</strong> Manager</p>',
                'versions' => [[
                    'id' => 'VER-JR6L-WYDT',
                    'version' => '1.0.0',
                    'changelog_html' => '<p>First version</p>',
                    'loaders' => ['Standalone'],
                    'game_versions' => [],
                    'file' => ['stored' => 'file_abc.zip', 'orig' => 'Staff-Manager.zip', 'size' => 14],
                    'created_at' => 1783981566,
                ]],
            ]],
            'license_codes' => [],
            'orders' => [[
                'order_id' => 'ORD-4TUC-2DDE',
                'email' => 'kunde@example.com',
                'items' => [['product_id' => 'PRD-YMUP-XS2A', 'name' => 'Staff Manager', 'price' => 5.99, 'original_price' => 5.99, 'discount' => 0, 'bearer_vendor_pct' => 100, 'qty' => 1]],
                'total' => 5.99,
                'payment_method' => 'sepa',
                'status' => 'fulfilled',
                'created_at' => 1783981664,
                'fulfilled_at' => 1783981693,
            ]],
            'transactions' => [[
                'id' => 'TXN-QT3R-W5BM-YLK3',
                'studio' => 'Media Tech Wilms',
                'order_id' => 'ORD-4TUC-2DDE',
                'units' => 1,
                'gross' => 5.99,
                'sale_deduct' => 0,
                'sale_shop' => 0,
                'pay' => 0.3,
                'plat' => 1.99,
                'net' => 3.7,
                'status' => 'paid',
                'created_at' => 1783981693,
            ]],
            'settings' => ['max_ips' => 3, 'min_price' => 5, 'legal_impressum' => '<h2>Impressum</h2><p>Test GmbH</p>'],
        ];
    }
}

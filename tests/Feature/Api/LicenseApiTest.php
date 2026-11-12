<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Actions\Licensing\ManageSubkeys;
use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class LicenseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_index_describes_the_available_endpoints(): void
    {
        $this->getJson('/api/v1')->assertOk()->assertJsonPath('version', 'v1');
        $this->getJson('/api/v1/activate')->assertOk()->assertJsonStructure(['endpoints']);
    }

    public function test_activation_registers_an_instance_and_reuses_it_on_repeat_calls(): void
    {
        $license = License::factory()->create();
        $payload = ['key' => $license->key, 'product_id' => $license->plugin->public_id, 'name' => 'Lobby-01'];

        $instanceId = $this->postJson('/api/v1/activate', $payload)
            ->assertOk()
            ->assertJsonPath('status', 'valid')
            ->assertJsonPath('mode', 'owner')
            ->assertJsonPath('ip_limit', 2)
            ->assertJsonPath('active_ips', ['127.0.0.1'])
            ->json('instance_id');

        $this->assertMatchesRegularExpression('/^INST(-[A-Z0-9]{5}){2}$/', $instanceId);

        $this->postJson('/api/v1/activate', $payload + ['instance_id' => $instanceId])->assertOk();

        $this->assertSame(1, $license->activations()->count());
        $this->assertSame('Lobby-01', $license->activations()->sole()->name);
    }

    public function test_activation_enforces_the_ip_limit_per_license(): void
    {
        Setting::query()->create(['key' => 'max_ips', 'value' => 1]);

        $license = License::factory()->create();
        $license->activations()->create(['instance_id' => 'INST-AAAAA-BBBBB', 'ip' => '203.0.113.9', 'last_seen_at' => now()]);

        $this->postJson('/api/v1/activate', ['key' => $license->key, 'product_id' => $license->plugin->public_id])
            ->assertForbidden()
            ->assertJsonPath('reason', 'ip_limit_reached')
            ->assertJsonPath('active_ips', ['203.0.113.9']);
    }

    public function test_activation_rejects_wrong_products_unknown_revoked_and_expired_licenses(): void
    {
        $license = License::factory()->create();
        $other = $this->publishedPlugin();

        $this->postJson('/api/v1/activate', ['key' => $license->key, 'product_id' => $other->public_id])
            ->assertForbidden()->assertJsonPath('reason', 'product_not_licensed');

        $this->postJson('/api/v1/activate', ['key' => 'AALC-NOPE-NOPE-NOPE-NOPE', 'product_id' => $other->public_id])
            ->assertNotFound()->assertJsonPath('reason', 'unknown_key');

        $this->postJson('/api/v1/activate', ['key' => $license->key])
            ->assertBadRequest()->assertJsonPath('reason', 'bad_request');

        $this->postJson('/api/v1/activate', ['key' => $license->key, 'product_id' => $license->plugin->public_id, 'instance_id' => 'bad id!'])
            ->assertBadRequest()->assertJsonPath('reason', 'bad_instance_id');

        $revoked = License::factory()->revoked()->create();
        $this->postJson('/api/v1/activate', ['key' => $revoked->key, 'product_id' => $revoked->plugin->public_id])
            ->assertForbidden()->assertJsonPath('reason', 'license_revoked');

        $expired = License::factory()->expired()->create();
        $this->postJson('/api/v1/activate', ['key' => $expired->key, 'product_id' => $expired->plugin->public_id])
            ->assertForbidden()->assertJsonPath('reason', 'license_expired');
    }

    public function test_deactivation_removes_the_instance_and_frees_its_ip(): void
    {
        $license = License::factory()->create();
        $license->activations()->create(['instance_id' => 'INST-AAAAA-BBBBB', 'ip' => '203.0.113.9', 'last_seen_at' => now()]);

        $this->postJson('/api/v1/deactivate', ['key' => $license->key, 'instance_id' => 'INST-AAAAA-BBBBB'])
            ->assertOk()
            ->assertJsonPath('removed', 1)
            ->assertJsonPath('active_ips', []);

        $this->postJson('/api/v1/deactivate', ['key' => 'unknown', 'instance_id' => 'x'])->assertNotFound();
    }

    public function test_verification_is_read_only_and_reports_the_reason(): void
    {
        $license = License::factory()->create();
        $query = ['key' => $license->key, 'product_id' => $license->plugin->public_id];

        $this->getJson('/api/v1/verify?'.http_build_query($query))
            ->assertOk()
            ->assertExactJson(['valid' => true, 'genuine' => true, 'mode' => 'owner', 'product' => $license->plugin->name, 'reason' => 'ok']);

        $this->postJson('/api/v1/verify', ['key' => $license->key, 'product_id' => 'PRD-XXXX-XXXX'])
            ->assertForbidden()->assertJsonPath('genuine', true)->assertJsonPath('reason', 'product_not_licensed');

        $this->getJson('/api/v1/verify?key=nope&product_id=nope')
            ->assertNotFound()->assertJsonPath('genuine', false);

        $this->assertDatabaseCount('license_activations', 0);
    }

    public function test_subkeys_bind_to_a_single_instance_and_follow_the_owner_license(): void
    {
        $owner = User::factory()->create();
        $license = License::factory()->create(['user_id' => $owner->id]);
        $subkey = app(ManageSubkeys::class)->create($owner, 'Friend', [$license->plugin_id]);
        $payload = ['key' => $subkey->key, 'product_id' => $license->plugin->public_id];

        $instanceId = $this->postJson('/api/v1/activate', $payload)
            ->assertOk()->assertJsonPath('mode', 'subuser')->json('instance_id');

        $this->postJson('/api/v1/activate', $payload + ['instance_id' => 'INST-OTHER-OTHER'])
            ->assertForbidden()->assertJsonPath('reason', 'subuser_bound_other_instance');

        $this->postJson('/api/v1/activate', $payload + ['instance_id' => $instanceId])->assertOk();
        $this->getJson('/api/v1/verify?'.http_build_query($payload))->assertOk()->assertJsonPath('mode', 'subuser');

        $license->update(['status' => 'revoked']);
        $this->postJson('/api/v1/activate', $payload)->assertForbidden()->assertJsonPath('reason', 'owner_lost_product');

        $license->update(['status' => 'active']);
        app(ManageSubkeys::class)->revoke($subkey);
        $this->postJson('/api/v1/activate', $payload)->assertForbidden()->assertJsonPath('reason', 'subuser_revoked');
    }

    public function test_update_check_reports_new_versions_and_signs_downloads_for_license_holders(): void
    {
        $plugin = $this->publishedPlugin(['price_cents' => 1500], '1.0.0');
        $this->releaseFor($plugin, '1.2.0')->update(['published_at' => now()->addMinute()]);
        $license = License::factory()->create(['plugin_id' => $plugin->id]);

        $this->getJson('/api/v1/updates/check?product_id='.$plugin->public_id.'&version=1.0.0')
            ->assertOk()
            ->assertJsonPath('latest_version', '1.2.0')
            ->assertJsonPath('update_available', true)
            ->assertJsonPath('entitled', false)
            ->assertJsonPath('download_url', null);

        $url = $this->getJson('/api/v1/updates/check?product_id='.$plugin->public_id.'&version=1.2.0&key='.$license->key)
            ->assertOk()
            ->assertJsonPath('update_available', false)
            ->assertJsonPath('entitled', true)
            ->json('download_url');

        $this->get($url)->assertOk()->assertDownload();
        $this->assertDatabaseHas('downloads', ['plugin_id' => $plugin->id, 'license_id' => $license->id]);

        $this->getJson('/api/v1/updates/check?product_id=PRD-NONE-NONE')->assertNotFound()->assertJsonPath('reason', 'unknown_product');
        $this->getJson('/api/v1/updates/check')->assertUnprocessable();
    }

    public function test_license_endpoints_are_rate_limited(): void
    {
        config(['marketplace.api.rate_limit_per_minute' => 3]);

        foreach (range(1, 3) as $ignored) {
            $this->getJson('/api/v1/verify?key=a&product_id=b')->assertNotFound();
        }

        $this->getJson('/api/v1/verify?key=a&product_id=b')->assertTooManyRequests();
    }
}

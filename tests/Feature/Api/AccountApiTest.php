<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\License;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_public_catalog_endpoints_expose_published_plugins(): void
    {
        $plugin = $this->publishedPlugin(['name' => 'Api Plugin', 'price_cents' => 1299]);

        $this->getJson('/api/v1/plugins')
            ->assertOk()
            ->assertJsonPath('data.0.id', $plugin->public_id)
            ->assertJsonPath('data.0.price.amount', 1299);

        $this->getJson('/api/v1/plugins/'.$plugin->public_id)
            ->assertOk()
            ->assertJsonPath('data.latest_release.version', '1.0.0');

        $this->getJson('/api/v1/plugins/'.$plugin->public_id.'/versions')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_account_endpoints_require_a_token_with_the_matching_ability(): void
    {
        $user = User::factory()->create();
        $plugin = $this->publishedPlugin();
        $license = License::factory()->create(['user_id' => $user->id, 'plugin_id' => $plugin->id]);

        $this->getJson('/api/v1/licenses')->assertUnauthorized();

        Sanctum::actingAs($user, ['licenses:read']);

        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', $user->email);
        $this->getJson('/api/v1/licenses')->assertOk()->assertJsonPath('data.0.key', $license->key);
        $this->getJson('/api/v1/licenses/'.$license->key.'/download')->assertForbidden();

        Sanctum::actingAs($user, ['downloads:read']);

        $url = $this->getJson('/api/v1/licenses/'.$license->key.'/download')
            ->assertOk()
            ->assertJsonPath('version', '1.0.0')
            ->json('download_url');

        $this->get($url)->assertOk()->assertDownload();
    }

    public function test_tokens_cannot_access_foreign_licenses(): void
    {
        $license = License::factory()->create();

        Sanctum::actingAs(User::factory()->create(), ['downloads:read']);

        $this->getJson('/api/v1/licenses/'.$license->key.'/download')->assertNotFound();
    }
}

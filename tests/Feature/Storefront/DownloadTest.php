<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Enums\LicenseSource;
use App\Models\License;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_downloads_require_authentication_and_a_license(): void
    {
        $plugin = $this->publishedPlugin(['price_cents' => 999]);
        $release = $plugin->latestRelease;

        $this->get(route('downloads.start', $release))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('downloads.start', $release))->assertForbidden();
    }

    public function test_license_holders_receive_a_signed_short_lived_link(): void
    {
        $customer = User::factory()->create();
        $plugin = $this->publishedPlugin(['price_cents' => 999]);
        $license = License::factory()->create(['user_id' => $customer->id, 'plugin_id' => $plugin->id]);
        $release = $plugin->latestRelease;

        $location = $this->actingAs($customer)->get(route('downloads.start', $release))->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString('signature=', $location);

        $this->get($location)
            ->assertOk()
            ->assertDownload('release.zip')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame(1, $plugin->refresh()->downloads_count);
        $this->assertSame(1, $release->refresh()->downloads_count);
        $this->assertDatabaseHas('downloads', ['user_id' => $customer->id, 'license_id' => $license->id]);

        $this->get(str_replace('signature=', 'signature=0', $location))->assertForbidden();

        $this->travel(config('marketplace.downloads.link_ttl_seconds') + 5)->seconds();
        $this->get($location)->assertForbidden();
    }

    public function test_unsigned_file_urls_are_rejected(): void
    {
        $release = $this->publishedPlugin()->latestRelease;

        $this->get(route('downloads.file', $release))->assertForbidden();
    }

    public function test_free_plugins_issue_a_license_on_first_download(): void
    {
        $customer = User::factory()->create();
        $plugin = $this->publishedPlugin(['price_cents' => 0]);

        $this->actingAs($customer)->get(route('downloads.start', $plugin->latestRelease))->assertRedirect();

        $this->assertSame(LicenseSource::Free, $customer->licenses()->sole()->source);
    }

    public function test_expired_or_revoked_licenses_lose_download_access(): void
    {
        $customer = User::factory()->create();
        $plugin = $this->publishedPlugin(['price_cents' => 999]);
        License::factory()->expired()->create(['user_id' => $customer->id, 'plugin_id' => $plugin->id]);

        $this->actingAs($customer)->get(route('downloads.start', $plugin->latestRelease))->assertForbidden();
    }

    public function test_release_archives_are_not_publicly_reachable(): void
    {
        $release = $this->publishedPlugin()->latestRelease;

        $this->get('/storage/'.$release->file_path)->assertNotFound();
        $this->get('/'.$release->file_path)->assertNotFound();
    }
}

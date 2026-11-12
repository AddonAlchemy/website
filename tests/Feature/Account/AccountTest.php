<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Enums\SubkeyStatus;
use App\Enums\UserRole;
use App\Models\License;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_customers_and_vendors_can_register(): void
    {
        $this->post(route('register.store'), [
            'account_type' => 'customer', 'email' => 'kunde@example.com', 'password' => 'sicheres-passwort', 'password_confirmation' => 'sicheres-passwort', 'terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertSame(UserRole::Customer, User::query()->where('email', 'kunde@example.com')->sole()->role);

        $this->post(route('logout'));

        $this->post(route('register.store'), [
            'account_type' => 'vendor', 'studio' => 'Nordlicht', 'email' => 'studio@example.com', 'password' => 'sicheres-passwort', 'password_confirmation' => 'sicheres-passwort', 'terms' => '1',
        ])->assertRedirect(route('vendor.dashboard'));

        $vendorUser = User::query()->where('email', 'studio@example.com')->sole();
        $this->assertSame(UserRole::Vendor, $vendorUser->role);
        $this->assertFalse($vendorUser->vendor->isApproved());
    }

    public function test_registration_validates_input_and_never_assigns_privileged_roles(): void
    {
        $this->post(route('register.store'), [
            'account_type' => 'admin', 'role' => 'admin', 'email' => 'x@example.com', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors(['account_type', 'password', 'terms']);

        $this->post(route('register.store'), ['account_type' => 'vendor', 'email' => 'v@example.com', 'password' => 'sicheres-passwort', 'password_confirmation' => 'sicheres-passwort', 'terms' => '1'])
            ->assertSessionHasErrors('studio');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_account_pages_render_and_are_protected(): void
    {
        $user = User::factory()->create();
        $routes = ['dashboard', 'account.profile.edit', 'account.orders.index', 'account.licenses.index', 'account.downloads.index', 'account.favorites.index', 'account.tokens.index', 'support.index'];

        foreach ($routes as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }

        License::factory()->create(['user_id' => $user->id, 'plugin_id' => $this->publishedPlugin()->id]);

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_profile_and_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'Neuer Name', 'first_name' => 'Erika', 'last_name' => 'Muster', 'street' => 'Weg 1', 'postal_code' => '10115', 'city' => 'Berlin', 'country' => 'de',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Neuer Name', 'DE'], [$user->refresh()->name, $user->country]);

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'wrong', 'password' => 'neues-passwort', 'password_confirmation' => 'neues-passwort',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'password', 'password' => 'neues-passwort', 'password_confirmation' => 'neues-passwort',
        ])->assertSessionHasNoErrors();
    }

    public function test_orders_and_invoices_are_only_visible_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);
        $invoice = $order->invoice()->create(['number' => 'RE-2026-000001', 'user_id' => $owner->id, 'total_cents' => 999, 'issued_at' => now()]);
        $stranger = User::factory()->create();

        $this->actingAs($owner)->get(route('account.orders.show', $order))->assertOk();
        $this->actingAs($owner)->get(route('account.invoices.show', $invoice))->assertOk()->assertSee('RE-2026-000001');
        $this->actingAs($stranger)->get(route('account.orders.show', $order))->assertForbidden();
        $this->actingAs($stranger)->get(route('account.invoices.show', $invoice))->assertForbidden();
    }

    public function test_license_keys_can_be_rotated_and_activations_released(): void
    {
        $user = User::factory()->create();
        $license = License::factory()->create(['user_id' => $user->id]);
        $activation = $license->activations()->create(['instance_id' => 'INST-AAAAA-BBBBB', 'ip' => '203.0.113.9', 'last_seen_at' => now()]);
        $license->activations()->create(['instance_id' => 'INST-CCCCC-DDDDD', 'ip' => '198.51.100.7', 'last_seen_at' => now()]);
        $originalKey = $license->key;

        $this->actingAs(User::factory()->create())->post(route('account.licenses.rotate', $license))->assertForbidden();
        $this->actingAs(User::factory()->create())->delete(route('account.activations.destroy', $activation))->assertForbidden();

        $this->actingAs($user)->delete(route('account.licenses.release-ip', $license), ['ip' => '203.0.113.9'])->assertSessionHas('success');
        $this->assertSame(['198.51.100.7'], $license->activations()->pluck('ip')->all());

        $this->actingAs($user)->post(route('account.licenses.rotate', $license))->assertSessionHas('success');
        $this->assertNotSame($originalKey, $license->refresh()->key);
        $this->assertSame(0, $license->activations()->count());
    }

    public function test_subkeys_respect_the_product_limit_and_the_reactivation_lock(): void
    {
        $user = User::factory()->create();
        $licenses = License::factory()->count(4)->create(['user_id' => $user->id]);
        $foreign = License::factory()->create();

        $this->actingAs($user)->post(route('account.subkeys.store'), ['plugins' => [$foreign->plugin_id]])->assertSessionHas('error');

        $this->actingAs($user)
            ->post(route('account.subkeys.store'), ['label' => 'Team', 'plugins' => $licenses->pluck('plugin_id')->all()])
            ->assertSessionHas('success');

        $subkey = $user->subkeys()->sole();
        $this->assertCount(3, $subkey->plugins);

        $this->actingAs($user)->patch(route('account.subkeys.revoke', $subkey))->assertSessionHas('success');
        $this->assertSame(SubkeyStatus::Revoked, $subkey->refresh()->status);

        $this->actingAs($user)->patch(route('account.subkeys.reactivate', $subkey))->assertSessionHas('error');

        $this->travel(15)->days();
        $this->actingAs($user)->patch(route('account.subkeys.reactivate', $subkey))->assertSessionHas('success');
        $this->assertTrue($subkey->refresh()->isActive());

        $this->actingAs(User::factory()->create())->delete(route('account.subkeys.destroy', $subkey))->assertForbidden();
    }

    public function test_api_tokens_are_shown_once_and_can_be_revoked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.tokens.store'), ['name' => 'CI', 'abilities' => ['licenses:read']])
            ->assertSessionHas('plainTextToken');

        $token = $user->tokens()->sole();
        $this->assertSame(['licenses:read'], $token->abilities);

        $this->actingAs($user)->post(route('account.tokens.store'), ['name' => 'Bad', 'abilities' => ['*']])->assertSessionHasErrors('abilities.0');

        $this->actingAs(User::factory()->create())->delete(route('account.tokens.destroy', $token->id));
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->actingAs($user)->delete(route('account.tokens.destroy', $token->id));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_favorites_can_be_toggled(): void
    {
        $user = User::factory()->create();
        $plugin = $this->publishedPlugin();

        $this->postJson(route('plugins.favorite', $plugin))->assertUnauthorized();
        $this->actingAs($user)->postJson(route('plugins.favorite', $plugin))->assertOk()->assertJson(['favorited' => true]);
        $this->actingAs($user)->get(route('account.favorites.index'))->assertSee($plugin->name);
        $this->actingAs($user)->postJson(route('plugins.favorite', $plugin))->assertJson(['favorited' => false]);
    }

    public function test_only_license_holders_can_review_and_ratings_are_aggregated(): void
    {
        $plugin = $this->publishedPlugin();
        $buyer = User::factory()->create();
        License::factory()->create(['user_id' => $buyer->id, 'plugin_id' => $plugin->id]);
        Review::factory()->create(['plugin_id' => $plugin->id, 'rating' => 2]);
        $payload = ['rating' => 5, 'title' => 'Stark', 'body' => 'Funktioniert einwandfrei auf unserem Netzwerk.'];

        $this->actingAs(User::factory()->create())->post(route('plugins.reviews.store', $plugin), $payload)->assertForbidden();
        $this->actingAs($plugin->vendor->user)->post(route('plugins.reviews.store', $plugin), $payload)->assertForbidden();

        $this->actingAs($buyer)->post(route('plugins.reviews.store', $plugin), $payload)->assertSessionHas('success');
        $this->actingAs($buyer)->post(route('plugins.reviews.store', $plugin), ['rating' => 4] + $payload);

        $plugin->refresh();
        $this->assertSame(2, $plugin->reviews_count);
        $this->assertSame(3.0, $plugin->rating_average);

        $review = $buyer->reviews()->sole();
        $this->actingAs($plugin->vendor->user)->post(route('vendor.reviews.reply', $review), ['reply' => 'Danke!'])->assertSessionHas('success');
        $this->get(route('plugins.show', $plugin))->assertSee('Danke!');

        $this->actingAs(User::factory()->create())->delete(route('reviews.destroy', $review))->assertForbidden();
        $this->actingAs($buyer)->delete(route('reviews.destroy', $review));
        $this->assertSame(1, $plugin->refresh()->reviews_count);
    }

    public function test_notifications_and_announcements_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin())->post(route('admin.announcements.store'), ['title' => 'Info', 'body' => 'Text', 'type' => 'info', 'audience' => 'all']);
        $user->notify(new \App\Notifications\ActivityNotification('Hallo', 'Welt'));

        $this->actingAs($user)->post(route('account.notifications.read'))->assertSessionHas('success');

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertDatabaseHas('announcement_reads', ['user_id' => $user->id]);
    }
}

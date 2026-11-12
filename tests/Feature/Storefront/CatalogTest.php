<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Platform;
use App\Models\Plugin;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_home_and_catalog_only_list_published_plugins(): void
    {
        $published = $this->publishedPlugin(['name' => 'Visible Plugin']);
        Plugin::factory()->draft()->create(['name' => 'Hidden Draft']);
        Plugin::factory()->pending()->create(['name' => 'Hidden Pending']);

        $this->get(route('home'))->assertOk()->assertSee('Visible Plugin');

        $this->get(route('plugins.index'))
            ->assertOk()
            ->assertSee($published->name)
            ->assertDontSee('Hidden Draft')
            ->assertDontSee('Hidden Pending');
    }

    public function test_catalog_filters_by_category_price_platform_and_search(): void
    {
        $economy = Category::factory()->create(['slug' => 'economy']);
        $paper = Platform::query()->create(['name' => 'Paper', 'slug' => 'paper']);

        $match = $this->publishedPlugin(['name' => 'Coin Vault', 'category_id' => $economy->id, 'price_cents' => 0]);
        $match->platforms()->attach($paper);
        $this->publishedPlugin(['name' => 'Paid Arena', 'price_cents' => 1999]);

        $this->get(route('plugins.index', ['category' => 'economy', 'price' => 'free', 'platform' => 'paper', 'q' => 'coin']))
            ->assertOk()
            ->assertSee('Coin Vault')
            ->assertDontSee('Paid Arena');

        $this->get(route('plugins.index', ['price' => 'paid']))->assertSee('Paid Arena')->assertDontSee('Coin Vault');
    }

    public function test_catalog_sorts_by_price_and_rating(): void
    {
        $this->publishedPlugin(['name' => 'Cheap One', 'price_cents' => 500, 'rating_average' => 3.1]);
        $this->publishedPlugin(['name' => 'Pricey One', 'price_cents' => 5000, 'rating_average' => 4.9]);

        $this->get(route('plugins.index', ['sort' => 'price_asc']))->assertSeeInOrder(['Cheap One', 'Pricey One']);
        $this->get(route('plugins.index', ['sort' => 'rating']))->assertSeeInOrder(['Pricey One', 'Cheap One']);
    }

    public function test_ajax_requests_receive_only_the_result_fragment(): void
    {
        $this->publishedPlugin(['name' => 'Fragment Plugin']);

        $this->get(route('plugins.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Fragment Plugin')
            ->assertDontSee('<html', false);
    }

    public function test_rejects_invalid_filter_values(): void
    {
        $this->get(route('plugins.index', ['sort' => 'drop table', 'rating' => 9]))->assertSessionHasErrors(['sort', 'rating']);
    }

    public function test_live_search_returns_matching_suggestions(): void
    {
        $plugin = $this->publishedPlugin(['name' => 'Vault Market']);
        $this->publishedPlugin(['name' => 'Other Thing']);

        $this->getJson(route('search.suggest', ['q' => 'vaul']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.name', 'Vault Market')
            ->assertJsonPath('results.0.url', route('plugins.show', $plugin));

        $this->getJson(route('search.suggest', ['q' => 'v']))->assertJsonCount(0, 'results');
    }

    public function test_detail_page_shows_sale_price_and_hides_unpublished_plugins(): void
    {
        $plugin = $this->publishedPlugin(['price_cents' => 2000]);
        Sale::query()->create(['name' => 'Spring Deal', 'percent' => 25]);

        $this->get(route('plugins.show', $plugin))
            ->assertOk()
            ->assertSee('15,00 €')
            ->assertSee('Spring Deal');

        $draft = Plugin::factory()->draft()->create();

        $this->get(route('plugins.show', $draft))->assertForbidden();
        $this->actingAs($draft->vendor->user)->get(route('plugins.show', $draft))->assertOk();
    }

    public function test_markdown_descriptions_are_rendered_without_raw_html(): void
    {
        $plugin = $this->publishedPlugin([
            'description' => "## Heading\n\n<script>alert('xss')</script>\n\n[bad](javascript:alert(1))",
        ]);

        $this->get(route('plugins.show', $plugin))
            ->assertOk()
            ->assertSee('<h2>Heading</h2>', false)
            ->assertDontSee("<script>alert('xss')</script>", false)
            ->assertDontSee('javascript:alert(1)', false);
    }

    public function test_legacy_urls_redirect_to_their_new_location(): void
    {
        $plugin = $this->publishedPlugin();

        $this->get('/shop?q=test')->assertRedirect(route('plugins.index', ['q' => 'test']));
        $this->get('/product?id='.$plugin->public_id)->assertRedirect(route('plugins.show', $plugin));
        $this->get('/team')->assertRedirect('/vendors');
    }
}

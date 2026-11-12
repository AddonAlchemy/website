<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Actions\Plugins\RefreshPluginRelease;
use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementType;
use App\Enums\LicenseCodeStatus;
use App\Enums\LicenseSource;
use App\Enums\OrderStatus;
use App\Enums\PluginStatus;
use App\Enums\SaleScope;
use App\Enums\SettlementStatus;
use App\Enums\SubkeyStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Enums\VersionStatus;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseCode;
use App\Models\LicenseSubkey;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Models\Sale;
use App\Models\Settlement;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use App\Services\KeyGenerator;
use App\Services\ReleaseStorage;
use App\Services\Settings;
use App\Support\HtmlToMarkdown;
use App\Support\UniqueSlug;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class LegacyImporter
{
    private const SETTING_KEYS = [
        'brand_first' => 'brand_first',
        'brand_second' => 'brand_second',
        'tagline' => 'tagline',
        'accent_color' => 'accent_color',
        'currency' => 'currency',
        'maintenance' => 'maintenance',
        'maintenance_msg' => 'maintenance_message',
        'max_ips' => 'max_ips',
        'subuser_max_products' => 'subkey_max_plugins',
        'subuser_lock_days' => 'subkey_lock_days',
        'platform_fee_pct' => 'platform_fee_percent',
        'company_name' => 'company_name',
        'company_address' => 'company_address',
        'company_email' => 'company_email',
        'cookie_enabled' => 'cookie_enabled',
        'cookie_text' => 'cookie_text',
    ];

    private const LEGAL_KEYS = [
        'legal_impressum' => 'legal_imprint',
        'legal_datenschutz' => 'legal_privacy',
        'legal_tos' => 'legal_terms',
        'legal_seller' => 'legal_vendor_terms',
    ];

    private string $basePath;

    /** @var array<string, int> */
    private array $report = [];

    public function __construct(
        private readonly Settings $settings,
        private readonly KeyGenerator $keys,
        private readonly RefreshPluginRelease $refreshRelease,
    ) {
    }

    /**
     * @return array<string, int>
     *
     * @throws RuntimeException
     */
    public function import(string $path, string $categorySlug): array
    {
        $this->basePath = rtrim($path, '/');
        $file = $this->basePath.'/data/db.json';

        if (! is_file($file)) {
            throw new RuntimeException('Keine Alt-Datenbank unter '.$file.' gefunden.');
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            throw new RuntimeException('Die Alt-Datenbank konnte nicht gelesen werden.');
        }

        $category = Category::query()->where('slug', $categorySlug)->first()
            ?? throw new RuntimeException('Kategorie '.$categorySlug.' existiert nicht. Bitte zuerst die Seeder ausführen.');

        $this->report = [];

        DB::transaction(function () use ($data, $category): void {
            $this->importSettings($data['settings'] ?? []);
            $this->importUsers($data);
            $this->importPlugins($data['products'] ?? [], $category);
            $this->importLicenseCodes($data['license_codes'] ?? []);
            $this->importLicenses($data['kunden'] ?? []);
            $this->importOrders($data['orders'] ?? []);
            $this->importSettlements($data['transactions'] ?? []);
            $this->importSales($data['sales'] ?? []);
            $this->importTickets($data['tickets'] ?? []);
            $this->importAnnouncements($data['notifications'] ?? []);
        });

        return $this->report;
    }

    /**
     * @param  array<string, mixed>  $legacy
     */
    private function importSettings(array $legacy): void
    {
        $values = [];

        foreach (self::SETTING_KEYS as $old => $new) {
            if (array_key_exists($old, $legacy)) {
                $values[$new] = $legacy[$old];
            }
        }

        foreach (self::LEGAL_KEYS as $old => $new) {
            if (filled($legacy[$old] ?? null)) {
                $values[$new] = HtmlToMarkdown::convert($legacy[$old]);
            }
        }

        if (isset($legacy['min_price'])) {
            $values['min_price_cents'] = $this->cents($legacy['min_price']);
        }

        if (is_array($legacy['payment_methods'] ?? null)) {
            $values['payment_methods'] = array_map(fn (array $method): array => [
                'id' => (string) $method['id'],
                'label' => (string) $method['label'],
                'percent' => (float) ($method['pct'] ?? 0),
                'fixed_cents' => $this->cents($method['fixed'] ?? 0),
            ], $legacy['payment_methods']);
        }

        $this->settings->put($values);
        $this->count('Einstellungen', count($values));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importUsers(array $data): void
    {
        foreach ($data['admins'] ?? [] as $admin) {
            $this->importUser($admin, UserRole::Admin);
        }

        foreach ($data['kunden'] ?? [] as $customer) {
            $this->importUser($customer, UserRole::Customer);
        }

        foreach ($data['developer'] ?? [] as $developer) {
            $user = $this->importUser($developer, UserRole::Vendor);
            $studio = trim((string) ($developer['studio'] ?? ''));

            if ($studio === '' || $user->vendor()->exists() || Vendor::query()->where('name', $studio)->exists()) {
                continue;
            }

            $approved = ! empty($developer['approved']);

            $user->vendor()->create([
                'name' => $studio,
                'slug' => UniqueSlug::for(Vendor::class, $studio),
                'status' => $approved ? VendorStatus::Approved : VendorStatus::Pending,
                'approved_at' => $approved ? now() : null,
            ]);
            $this->count('Studios');
        }
    }

    /**
     * @param  array<string, mixed>  $legacy
     */
    private function importUser(array $legacy, UserRole $role): User
    {
        $email = Str::lower(trim((string) $legacy['email']));
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            return $existing;
        }

        $user = new User();
        $user->setRawAttributes([
            'name' => filled($legacy['username'] ?? null) ? $legacy['username'] : Str::before($email, '@'),
            'email' => $email,
            'password' => (string) $legacy['password'],
            'role' => $role->value,
            'first_name' => $legacy['firstname'] ?? null ?: null,
            'last_name' => $legacy['lastname'] ?? null ?: null,
            'street' => $legacy['address'] ?? null ?: null,
            'postal_code' => $legacy['zip'] ?? null ?: null,
            'city' => $legacy['city'] ?? null ?: null,
            'country' => 'DE',
            'email_verified_at' => now(),
        ]);
        $user->save();

        $this->count('Benutzer');

        return $user;
    }

    /**
     * @param  list<array<string, mixed>>  $products
     */
    private function importPlugins(array $products, Category $category): void
    {
        foreach ($products as $product) {
            $vendor = Vendor::query()->where('name', $product['vendor'] ?? '')->first();

            if ($vendor === null || Plugin::query()->where('public_id', $product['product_id'])->exists()) {
                continue;
            }

            $createdAt = Carbon::createFromTimestamp((int) ($product['created_at'] ?? time()));
            $description = $this->relocateInlineImages(HtmlToMarkdown::convert($product['description_html'] ?? ''));

            $plugin = Plugin::query()->create([
                'public_id' => $product['product_id'],
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'name' => $product['name'],
                'slug' => UniqueSlug::for(Plugin::class, (string) $product['name']),
                'summary' => Str::limit(trim(strip_tags((string) ($product['description_html'] ?? ''))) ?: $product['name'], 250),
                'description' => $description,
                'icon_path' => $this->copyMedia($product['image'] ?? null),
                'price_cents' => $this->cents($product['price'] ?? 0),
                'status' => PluginStatus::Approved,
                'published_at' => $createdAt,
                'created_at' => $createdAt,
            ]);
            $this->count('Plugins');

            foreach (array_reverse($product['versions'] ?? []) as $version) {
                $this->importVersion($plugin, $version);
            }

            $this->refreshRelease->handle($plugin);
        }
    }

    /**
     * @param  array<string, mixed>  $legacy
     */
    private function importVersion(Plugin $plugin, array $legacy): void
    {
        $source = $this->basePath.'/protected/files/'.basename((string) ($legacy['file']['stored'] ?? ''));

        if (! is_file($source) || PluginVersion::query()->where('public_id', $legacy['id'])->exists()) {
            return;
        }

        $path = config('marketplace.releases.directory').'/'.$plugin->public_id.'/'.Str::uuid().'.'.pathinfo($source, PATHINFO_EXTENSION);
        Storage::disk(config('marketplace.releases.disk'))->put($path, (string) file_get_contents($source));

        $releasedAt = Carbon::createFromTimestamp((int) ($legacy['created_at'] ?? time()));

        $version = $plugin->versions()->create([
            'public_id' => $legacy['id'],
            'version' => $legacy['version'],
            'changelog' => HtmlToMarkdown::convert($legacy['changelog_html'] ?? ''),
            'game_versions' => array_values($legacy['game_versions'] ?? []),
            'status' => VersionStatus::Approved,
            'file_path' => $path,
            'file_name' => ReleaseStorage::sanitizeFileName((string) ($legacy['file']['orig'] ?? basename($source))),
            'file_size' => (int) filesize($source),
            'file_hash' => (string) hash_file('sha256', $source),
            'published_at' => $releasedAt,
            'created_at' => $releasedAt,
        ]);

        $version->platforms()->sync(collect($legacy['loaders'] ?? [])->map(
            static fn (string $loader): int => Platform::query()->firstOrCreate(
                ['slug' => Str::slug($loader)],
                ['name' => $loader, 'position' => 99],
            )->id,
        )->all());

        $this->count('Versionen');
    }

    /**
     * @param  list<array<string, mixed>>  $codes
     */
    private function importLicenseCodes(array $codes): void
    {
        foreach ($codes as $code) {
            $plugin = Plugin::query()->where('public_id', $code['product_id'] ?? '')->first();

            if ($plugin === null || LicenseCode::query()->where('code', $code['code'])->exists()) {
                continue;
            }

            $plugin->licenseCodes()->create([
                'code' => $code['code'],
                'vendor_id' => $plugin->vendor_id,
                'status' => LicenseCodeStatus::tryFrom((string) ($code['status'] ?? '')) ?? LicenseCodeStatus::Unused,
                'note' => $code['note'] ?? null ?: null,
                'redeemed_by' => User::query()->where('email', $code['redeemed_by'] ?? '')->value('id'),
                'redeemed_at' => $this->timestamp($code['redeemed_at'] ?? null),
            ]);
            $this->count('Lizenz-Codes');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $customers
     */
    private function importLicenses(array $customers): void
    {
        foreach ($customers as $customer) {
            $user = User::query()->where('email', Str::lower((string) $customer['email']))->first();

            if ($user === null) {
                continue;
            }

            $legacyKey = $customer['license_key'] ?? null;
            $plugins = Plugin::query()->whereIn('public_id', $customer['products'] ?? [])->get()->keyBy('public_id');

            foreach ($plugins->values() as $index => $plugin) {
                $reuseLegacyKey = $index === 0 && filled($legacyKey) && ! License::query()->where('key', $legacyKey)->exists();

                $license = License::query()->firstOrCreate(
                    ['user_id' => $user->id, 'plugin_id' => $plugin->id],
                    ['key' => $reuseLegacyKey ? $legacyKey : $this->keys->licenseKey(), 'source' => LicenseSource::Grant],
                );

                if (! $license->wasRecentlyCreated) {
                    continue;
                }

                $this->count('Lizenzen');

                foreach ($customer['license']['instances'] ?? [] as $instance) {
                    if (($instance['product_id'] ?? null) !== $plugin->public_id || blank($instance['ip'] ?? null)) {
                        continue;
                    }

                    $license->activations()->create([
                        'instance_id' => $instance['instance_id'],
                        'ip' => $instance['ip'],
                        'name' => $instance['name'] ?? null ?: null,
                        'last_seen_at' => $this->timestamp($instance['last_seen'] ?? null),
                    ]);
                    $this->count('Aktivierungen');
                }
            }

            foreach ($customer['license']['subusers'] ?? [] as $subuser) {
                if (LicenseSubkey::query()->where('key', $subuser['key'])->exists()) {
                    continue;
                }

                $subkey = $user->subkeys()->create([
                    'key' => $subuser['key'],
                    'label' => $subuser['label'] ?? null ?: null,
                    'instance_id' => $subuser['instance_id'] ?? null,
                    'status' => SubkeyStatus::tryFrom((string) ($subuser['status'] ?? '')) ?? SubkeyStatus::Active,
                    'revoked_at' => $this->timestamp($subuser['revoked_at'] ?? null),
                    'last_seen_at' => $this->timestamp($subuser['last_seen'] ?? null),
                ]);
                $subkey->plugins()->sync($plugins->only($subuser['product_ids'] ?? [])->pluck('id')->all());
                $this->count('Subuser-Keys');
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $orders
     */
    private function importOrders(array $orders): void
    {
        $methods = collect($this->settings->paymentMethods())->keyBy('id');

        foreach ($orders as $legacy) {
            $user = User::query()->where('email', Str::lower((string) ($legacy['email'] ?? '')))->first();

            if ($user === null || Order::query()->where('number', $legacy['order_id'])->exists()) {
                continue;
            }

            $status = OrderStatus::tryFrom((string) ($legacy['status'] ?? '')) ?? OrderStatus::Pending;
            $method = $methods[$legacy['payment_method'] ?? ''] ?? $methods->first();
            $items = collect($legacy['items'] ?? []);
            $createdAt = $this->timestamp($legacy['created_at'] ?? null) ?? now();
            $subtotal = (int) $items->sum(fn (array $item): int => $this->cents($item['original_price'] ?? $item['price'] ?? 0));
            $total = $this->cents($legacy['total'] ?? 0);

            $order = $user->orders()->create([
                'number' => $legacy['order_id'],
                'status' => $status,
                'payment_method' => $method->id,
                'payment_method_label' => $method->label,
                'payment_fee_percent' => $method->percent,
                'payment_fee_fixed_cents' => $method->fixedCents,
                'subtotal_cents' => max($subtotal, $total),
                'discount_cents' => max(0, $subtotal - $total),
                'total_cents' => $total,
                'billing' => $user->billingSnapshot(),
                'fulfilled_at' => $this->timestamp($legacy['fulfilled_at'] ?? null),
                'cancelled_at' => $status === OrderStatus::Cancelled ? $createdAt : null,
                'created_at' => $createdAt,
            ]);

            foreach ($items as $item) {
                $plugin = Plugin::query()->where('public_id', $item['product_id'] ?? '')->first();

                $order->items()->create([
                    'plugin_id' => $plugin?->id,
                    'vendor_id' => $plugin?->vendor_id,
                    'plugin_name' => $item['name'] ?? $item['product_id'],
                    'plugin_public_id' => $item['product_id'],
                    'license_model' => $plugin?->license_model->value ?? 'perpetual',
                    'original_price_cents' => $this->cents($item['original_price'] ?? $item['price'] ?? 0),
                    'discount_cents' => $this->cents($item['discount'] ?? 0),
                    'unit_price_cents' => $this->cents($item['price'] ?? 0),
                    'vendor_discount_share' => (int) ($item['bearer_vendor_pct'] ?? 100),
                ]);
            }

            if ($status === OrderStatus::Fulfilled) {
                $order->invoice()->create([
                    'number' => sprintf('RE-%s-%06d', ($order->fulfilled_at ?? $createdAt)->format('Y'), $order->id),
                    'user_id' => $user->id,
                    'billing' => $order->billing,
                    'total_cents' => $total,
                    'issued_at' => $order->fulfilled_at ?? $createdAt,
                ]);
            }

            $this->count('Bestellungen');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $transactions
     */
    private function importSettlements(array $transactions): void
    {
        foreach ($transactions as $legacy) {
            $vendor = Vendor::query()->where('name', $legacy['studio'] ?? '')->first();
            $order = Order::query()->where('number', $legacy['order_id'] ?? '')->first();

            if ($vendor === null || $order === null || Settlement::query()->where('number', $legacy['id'])->exists()) {
                continue;
            }

            $status = ($legacy['status'] ?? '') === 'paid'
                ? SettlementStatus::Available
                : (SettlementStatus::tryFrom((string) ($legacy['status'] ?? '')) ?? SettlementStatus::Pending);

            Settlement::query()->create([
                'number' => $legacy['id'],
                'vendor_id' => $vendor->id,
                'order_id' => $order->id,
                'units' => (int) ($legacy['units'] ?? 1),
                'gross_cents' => $this->cents($legacy['gross'] ?? 0),
                'vendor_discount_cents' => $this->cents($legacy['sale_deduct'] ?? 0),
                'platform_discount_cents' => $this->cents($legacy['sale_shop'] ?? 0),
                'payment_fee_cents' => $this->cents($legacy['pay'] ?? 0),
                'platform_fee_cents' => $this->cents($legacy['plat'] ?? 0),
                'net_cents' => $this->cents($legacy['net'] ?? 0),
                'status' => $status,
                'released_at' => $this->timestamp($legacy['released_at'] ?? null),
                'paid_out_at' => $this->timestamp($legacy['paid_out_at'] ?? null),
                'created_at' => $this->timestamp($legacy['created_at'] ?? null) ?? now(),
            ]);
            $this->count('Abrechnungen');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $sales
     */
    private function importSales(array $sales): void
    {
        foreach ($sales as $legacy) {
            $vendor = ($legacy['scope'] ?? 'shop') === 'studio'
                ? Vendor::query()->where('name', $legacy['scope_studio'] ?? '')->first()
                : null;

            Sale::query()->create([
                'name' => $legacy['name'],
                'percent' => (float) ($legacy['percent'] ?? 0),
                'scope' => $vendor !== null ? SaleScope::Vendor : SaleScope::Shop,
                'vendor_id' => $vendor?->id,
                'vendor_share_percent' => (int) ($legacy['bearer_vendor_pct'] ?? 0),
                'is_active' => ! empty($legacy['active']),
                'starts_at' => $this->timestamp($legacy['starts_at'] ?? null),
                'ends_at' => $this->timestamp($legacy['ends_at'] ?? null),
            ]);
            $this->count('Sale-Aktionen');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $tickets
     */
    private function importTickets(array $tickets): void
    {
        foreach ($tickets as $legacy) {
            $creator = User::query()->where('email', Str::lower((string) ($legacy['creator'] ?? '')))->first();

            if ($creator === null || Ticket::query()->where('number', $legacy['id'])->exists()) {
                continue;
            }

            $ticket = $creator->tickets()->create([
                'number' => $legacy['id'],
                'vendor_id' => Vendor::query()->where('name', $legacy['target_studio'] ?? '')->value('id'),
                'plugin_id' => Plugin::query()->where('public_id', $legacy['product_id'] ?? '')->value('id'),
                'subject' => $legacy['subject'],
                'status' => TicketStatus::tryFrom((string) ($legacy['status'] ?? '')) ?? TicketStatus::Open,
                'last_reply_at' => $this->timestamp($legacy['updated_at'] ?? null),
                'created_at' => $this->timestamp($legacy['created_at'] ?? null) ?? now(),
            ]);

            foreach ($legacy['messages'] ?? [] as $message) {
                $author = User::query()->where('email', Str::lower((string) ($message['author'] ?? '')))->first();

                if ($author !== null) {
                    $ticket->messages()->create([
                        'user_id' => $author->id,
                        'body' => $message['body'],
                        'created_at' => $this->timestamp($message['created_at'] ?? null) ?? now(),
                    ]);
                }
            }

            $this->count('Tickets');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $notifications
     */
    private function importAnnouncements(array $notifications): void
    {
        $types = ['aktion' => AnnouncementType::Promotion, 'warnung' => AnnouncementType::Warning, 'wartung' => AnnouncementType::Maintenance];

        foreach ($notifications as $legacy) {
            $audience = match ($legacy['target'] ?? '') {
                '*' => AnnouncementAudience::All,
                'kunden' => AnnouncementAudience::Customers,
                'developer' => AnnouncementAudience::Vendors,
                default => null,
            };

            if ($audience === null) {
                continue;
            }

            $type = (string) ($legacy['type'] ?? 'info');

            Announcement::query()->create([
                'title' => Str::limit((string) $legacy['title'], 100, ''),
                'body' => Str::limit((string) $legacy['body'], 500, ''),
                'type' => $types[$type] ?? AnnouncementType::tryFrom($type) ?? AnnouncementType::Info,
                'audience' => $audience,
                'created_at' => $this->timestamp($legacy['created_at'] ?? null) ?? now(),
            ]);
            $this->count('Ankündigungen');
        }
    }

    private function relocateInlineImages(string $markdown): string
    {
        return (string) preg_replace_callback(
            '#\]\((/uploads/[\w.\-]+)\)#',
            function (array $match): string {
                $path = $this->copyMedia($match[1], 'plugins/content');

                return $path !== null
                    ? ']('.Storage::disk(config('marketplace.media.disk'))->url($path).')'
                    : $match[0];
            },
            $markdown,
        );
    }

    private function copyMedia(?string $webPath, string $directory = 'plugins/icons'): ?string
    {
        if (blank($webPath)) {
            return null;
        }

        $source = $this->basePath.'/uploads/'.basename($webPath);

        if (! is_file($source)) {
            return null;
        }

        $target = $directory.'/'.Str::uuid().'.'.pathinfo($source, PATHINFO_EXTENSION);
        Storage::disk(config('marketplace.media.disk'))->put($target, (string) file_get_contents($source));

        return $target;
    }

    private function cents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function timestamp(mixed $value): ?Carbon
    {
        return is_numeric($value) && (int) $value > 0 ? Carbon::createFromTimestamp((int) $value) : null;
    }

    private function count(string $area, int $amount = 1): void
    {
        $this->report[$area] = ($this->report[$area] ?? 0) + $amount;
    }
}

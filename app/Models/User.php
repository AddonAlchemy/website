<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasApiTokens;
    use Notifiable;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'role' => UserRole::Customer->value,
        'country' => 'DE',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * @return HasOne<Vendor, $this>
     */
    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * @return HasMany<LicenseSubkey, $this>
     */
    public function subkeys(): HasMany
    {
        return $this->hasMany(LicenseSubkey::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return HasMany<Download, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    /**
     * @return BelongsToMany<Plugin, $this>
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Plugin::class, 'favorites')->withPivot('created_at');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isVendor(): bool
    {
        return $this->role === UserRole::Vendor;
    }

    public function ownsVendor(?Vendor $vendor): bool
    {
        return $vendor !== null && $vendor->user_id === $this->id;
    }

    public function hasUsableLicenseFor(Plugin $plugin): bool
    {
        return $this->licenses()->usable()->where('plugin_id', $plugin->id)->exists();
    }

    /**
     * @return array{name: string, street: ?string, postal_code: ?string, city: ?string, country: string, email: string}
     */
    public function billingSnapshot(): array
    {
        return [
            'name' => trim($this->first_name.' '.$this->last_name) ?: $this->name,
            'street' => $this->street,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,
            'email' => $this->email,
        ];
    }
}

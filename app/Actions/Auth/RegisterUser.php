<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Vendors\ApplyAsVendor;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterUser
{
    public function __construct(private readonly ApplyAsVendor $applyAsVendor)
    {
    }

    /**
     * @param  array{email: string, password: string, name?: ?string, account_type: string, studio?: ?string}  $data
     */
    public function handle(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => filled($data['name'] ?? null) ? $data['name'] : Str::before($data['email'], '@'),
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            if ($data['account_type'] === 'vendor') {
                $this->applyAsVendor->handle($user, ['name' => (string) $data['studio']]);
            }

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}

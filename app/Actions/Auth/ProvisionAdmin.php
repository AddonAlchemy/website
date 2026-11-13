<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;

final class ProvisionAdmin
{
    public function handle(string $email, ?string $password = null, string $name = 'Admin'): User
    {
        $user = User::query()->firstOrNew(['email' => mb_strtolower(trim($email))]);

        if (! $user->exists) {
            $user->fill(['name' => $name, 'email_verified_at' => now()]);
        }

        if ($password !== null) {
            $user->password = $password;
        }

        $user->role = UserRole::Admin;
        $user->save();

        return $user;
    }
}

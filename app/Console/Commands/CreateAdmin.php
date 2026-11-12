<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class CreateAdmin extends Command
{
    protected $signature = 'marketplace:admin {email : E-Mail-Adresse des Administrators} {--name=Admin}';

    protected $description = 'Legt einen Administrator an oder befördert ein bestehendes Konto';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $existing->update(['role' => UserRole::Admin]);
            $this->components->info($email.' ist jetzt Administrator.');

            return self::SUCCESS;
        }

        $password = (string) $this->secret('Passwort');
        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', Password::defaults()]],
        );

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $this->components->info('Administrator '.$email.' wurde angelegt.');

        return self::SUCCESS;
    }
}

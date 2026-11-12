<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Licensing\IssueLicense;
use App\Actions\Licensing\RevokeLicense;
use App\Enums\LicenseSource;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GrantLicenseRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\License;
use App\Models\Plugin;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $role = UserRole::tryFrom((string) $request->query('role'));

        return view('admin.users.index', [
            'users' => User::query()
                ->with('vendor:id,user_id,name,status')
                ->withCount(['licenses', 'orders'])
                ->when($search !== '', static fn ($query) => $query->whereAny(['name', 'email'], 'like', '%'.$search.'%'))
                ->when($role, static fn ($query) => $query->where('role', $role))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'roles' => UserRole::cases(),
            'search' => $search,
            'role' => $role,
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user->load('vendor'),
            'licenses' => $user->licenses()->with('plugin:id,name,slug,public_id')->withCount('activations')->latest()->get(),
            'orders' => $user->orders()->latest()->limit(10)->get(),
            'plugins' => Plugin::query()->published()->orderBy('name')->get(['id', 'name', 'public_id']),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['role']) && $user->is($request->user()) && $data['role'] !== UserRole::Admin->value) {
            return back()->with('error', 'Du kannst dir die Admin-Rolle nicht selbst entziehen.');
        }

        $user->update($data);

        return back()->with('success', isset($data['password']) ? 'Passwort zurückgesetzt.' : 'Benutzer aktualisiert.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Du kannst dein eigenes Konto nicht löschen.');
        }

        if ($user->isAdmin() && User::query()->where('role', UserRole::Admin)->count() <= 1) {
            return back()->with('error', 'Der letzte Admin kann nicht gelöscht werden.');
        }

        $user->delete();

        return to_route('admin.users.index')->with('success', 'Benutzer gelöscht.');
    }

    public function grantLicense(GrantLicenseRequest $request, User $user, IssueLicense $issue): RedirectResponse
    {
        $issue->handle($user, Plugin::query()->findOrFail($request->validated('plugin_id')), LicenseSource::Grant);

        return back()->with('success', 'Produkt-Lizenz erteilt.');
    }

    public function revokeLicense(License $license, RevokeLicense $revoke): RedirectResponse
    {
        $revoke->handle($license);

        return back()->with('success', 'Produkt-Lizenz entzogen.');
    }
}

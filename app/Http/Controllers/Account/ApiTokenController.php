<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreApiTokenRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ApiTokenController extends Controller
{
    private const MAX_TOKENS = 10;

    public function index(Request $request): View
    {
        return view('account.tokens', [
            'tokens' => $request->user()->tokens()->latest()->get(),
            'abilities' => StoreApiTokenRequest::ABILITIES,
        ]);
    }

    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->tokens()->count() >= self::MAX_TOKENS) {
            return back()->with('error', 'Du kannst maximal '.self::MAX_TOKENS.' API-Tokens anlegen.');
        }

        $token = $user->createToken($request->validated('name'), $request->validated('abilities'));

        return back()
            ->with('success', 'API-Token erstellt. Kopiere ihn jetzt – er wird nur einmal angezeigt.')
            ->with('plainTextToken', $token->plainTextToken);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back()->with('success', 'API-Token widerrufen.');
    }
}

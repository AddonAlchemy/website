<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class LibraryController extends Controller
{
    public function downloads(Request $request): View
    {
        return view('account.downloads', [
            'downloads' => $request->user()->downloads()
                ->with(['plugin:id,name,slug', 'version:id,public_id,version,file_name,file_size'])
                ->latest('created_at')
                ->paginate(20),
        ]);
    }

    public function favorites(Request $request): View
    {
        return view('account.favorites', [
            'plugins' => $request->user()->favorites()
                ->published()
                ->with(['vendor:id,name,slug', 'category:id,name,slug,icon', 'platforms'])
                ->orderByPivot('created_at', 'desc')
                ->paginate(12),
        ]);
    }
}

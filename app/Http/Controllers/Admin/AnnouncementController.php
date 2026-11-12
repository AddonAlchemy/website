<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements', [
            'items' => Announcement::query()->withCount('readers')->latest()->paginate(20),
            'types' => AnnouncementType::cases(),
            'audiences' => AnnouncementAudience::cases(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        Announcement::query()->create([...$request->validated(), 'created_by' => $request->user()->id]);

        return back()->with('success', 'Ankündigung gesendet.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Ankündigung gelöscht.');
    }
}

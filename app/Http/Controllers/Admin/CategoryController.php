<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use App\Support\UniqueSlug;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories', [
            'categories' => Category::query()->withCount('plugins')->orderBy('position')->get(),
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        Category::query()->create([
            ...$request->validated(),
            'position' => $request->integer('position'),
            'slug' => UniqueSlug::for(Category::class, $request->validated('name')),
        ]);

        return back()->with('success', 'Kategorie angelegt.');
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update([...$request->validated(), 'position' => $request->integer('position')]);

        return back()->with('success', 'Kategorie gespeichert.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->plugins()->exists()) {
            return back()->with('error', 'Kategorien mit Plugins können nicht gelöscht werden.');
        }

        $category->delete();

        return back()->with('success', 'Kategorie gelöscht.');
    }
}

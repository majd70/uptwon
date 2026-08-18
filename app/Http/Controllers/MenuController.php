<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    public function landing(): View
    {
        // An uploaded cover is deliberate branding and gets shown as a real
        // banner. Without one, a dish photograph stands in as dimmed ambience.
        return view('landing', [
            'coverImage' => settings()->coverUrl(),
            'heroImage' => $this->heroImage(),
        ]);
    }

    /**
     * The photograph behind the restaurant name. Grills and main courses are
     * shot dark and dramatic; soups and salads are white bowls on white linen
     * and turn to grey mush once dimmed behind type. Prefer the former.
     */
    private function heroImage(): ?string
    {
        return Cache::remember('menu.hero', now()->addHours(12), function () {
            $dramatic = MenuItem::query()
                ->available()
                ->whereNotNull('image')
                ->whereHas('category', fn ($q) => $q->whereIn('name_en', ['Oriental Dishes', 'Main Courses']))
                ->orderBy('sort_order')
                ->first();

            // Blurred to 6px and scaled up, so the small thumbnail is
            // indistinguishable from the full file at a tenth of the bytes.
            $fallback = MenuItem::query()->available()->whereNotNull('image')->orderBy('sort_order')->first();

            return ($dramatic ?? $fallback)?->thumbUrl();
        });
    }


    public function menu(): View
    {
        return view('menu', ['categories' => $this->categories()]);
    }

    /**
     * Visible categories with their available items, cached until an admin save
     * or a menu:import busts the key.
     */
    private function categories()
    {
        return Cache::remember('menu.payload', now()->addHours(12), function () {
            return Category::query()
                ->visible()
                ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (Category $c) => $c->items->isNotEmpty())
                ->values();
        });
    }
}

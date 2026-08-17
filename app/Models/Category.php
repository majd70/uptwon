<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasBilingualAttributes;

    protected $fillable = [
        'name_ar', 'name_en', 'image', 'sort_order', 'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Not cached: the locale can change within a request. */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->localized('name'))->withoutObjectCaching();
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /** Stable DOM id for the menu page's scroll-spy anchors. */
    public function anchor(): string
    {
        return 'category-'.$this->id;
    }
}

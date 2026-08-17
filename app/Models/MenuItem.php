<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualAttributes;
use App\Services\ImageProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    use HasBilingualAttributes;

    protected $fillable = [
        'category_id', 'name_ar', 'name_en', 'description_ar', 'description_en',
        'price', 'image', 'is_available', 'is_featured', 'sort_order', 'tags',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'tags' => 'array',
    ];

    /** Not cached: the locale can change within a request. */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->localized('name'))->withoutObjectCaching();
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn () => $this->localized('description'))->withoutObjectCaching();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    /**
     * List-sized image. ImageProcessor writes a thumbnail alongside every image
     * it stores, so the path can be derived rather than looked up per row.
     */
    public function thumbUrl(): ?string
    {
        return $this->image
            ? Storage::disk('public')->url(ImageProcessor::thumbPath($this->image))
            : null;
    }

    /**
     * Price rendered for the current locale, or an em dash when the source
     * documents carried no price for this item.
     */
    public function formattedPrice(): string
    {
        if ($this->price === null) {
            return '—';
        }

        $amount = number_format((float) $this->price, 0, '.', app()->getLocale() === 'ar' ? '٬' : ',');

        return $amount.' '.__('menu.currency');
    }

    /** Text the client-side search matches against, both languages at once. */
    public function searchIndex(): string
    {
        return mb_strtolower(implode(' ', array_filter([
            $this->name_en, $this->name_ar, $this->description_en, $this->description_ar,
        ])));
    }
}

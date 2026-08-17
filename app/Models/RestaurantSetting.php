<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Single-row settings record. Read it through the `settings()` helper, which
 * serves it from cache; any save here busts that cache.
 */
class RestaurantSetting extends Model
{
    use HasBilingualAttributes;

    public const CACHE_KEY = 'restaurant.settings';

    protected $guarded = [];

    protected $casts = [
        'working_hours' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('menu.payload');
        Cache::forget('menu.showcase');
        Cache::forget('menu.hero');
    }

    public static function current(): self
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->firstOrNew([], [
                'name_ar' => 'أب تاون',
                'name_en' => 'Uptown Restaurant & Café',
            ])
        );
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->localized('name'))->withoutObjectCaching();
    }

    protected function tagline(): Attribute
    {
        return Attribute::get(fn () => $this->localized('tagline'))->withoutObjectCaching();
    }

    protected function address(): Attribute
    {
        return Attribute::get(fn () => $this->localized('address'))->withoutObjectCaching();
    }

    /**
     * The letters drawn inside the gold ring when there is no logo image.
     * Falls back to the initials of the English name.
     */
    public function monogramText(): string
    {
        if (filled($this->monogram)) {
            return $this->monogram;
        }

        $initials = collect(preg_split('/\s+/', (string) $this->name_en))
            ->reject(fn ($w) => in_array(mb_strtolower(trim($w, '&')), ['', '&', 'and', 'the'], true))
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'UT';
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null;
    }

    /** Digits-only phone number, safe to drop into a tel: or wa.me link. */
    public function whatsappNumber(): ?string
    {
        return $this->whatsapp ? preg_replace('/\D+/', '', $this->whatsapp) : null;
    }

    public function whatsappUrl(): ?string
    {
        $number = $this->whatsappNumber();

        return $number ? "https://wa.me/{$number}" : null;
    }

    /**
     * Working hours as a list of {label_ar, label_en, hours} rows, tolerating the
     * shape Filament's repeater produces and an empty/unset column.
     */
    public function hours(): array
    {
        return collect($this->working_hours ?? [])
            ->filter(fn ($row) => is_array($row) && filled($row['hours'] ?? null))
            ->values()
            ->all();
    }
}

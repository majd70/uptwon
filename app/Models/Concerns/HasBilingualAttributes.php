<?php

namespace App\Models\Concerns;

/**
 * Helper for `*_ar` / `*_en` column pairs: returns the current locale's value and
 * falls back to the other language when it is empty. The Arabic source menu does
 * not cover every item, so that fallback is the normal case, not an edge case.
 */
trait HasBilingualAttributes
{
    public function localized(string $field): ?string
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $other = $locale === 'ar' ? 'en' : 'ar';

        $value = $this->getAttribute("{$field}_{$locale}");

        return filled($value) ? $value : $this->getAttribute("{$field}_{$other}");
    }
}

<?php

use App\Models\RestaurantSetting;

if (! function_exists('settings')) {
    /**
     * The cached single-row restaurant settings record.
     *
     * settings() returns the model; settings('phone') returns one attribute.
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $settings = RestaurantSetting::current();

        if ($key === null) {
            return $settings;
        }

        return $settings->getAttribute($key) ?? $default;
    }
}

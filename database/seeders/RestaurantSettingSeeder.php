<?php

namespace Database\Seeders;

use App\Models\RestaurantSetting;
use Illuminate\Database\Seeder;

class RestaurantSettingSeeder extends Seeder
{
    public function run(): void
    {
        // Social links and contact details are deliberately left blank — they are
        // filled in from the admin dashboard, and re-seeding must not wipe them.
        RestaurantSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'أب تاون',
                'name_en' => 'Uptown Restaurant & Café',
                'tagline_ar' => 'مشويات · مأكولات بحرية · كافيه',
                'tagline_en' => 'Grill · Seafood · Café',
                'monogram' => 'UT',
                'primary_color' => '#3d4f2f',
                'secondary_color' => '#f5f1e8',
                'accent_color' => '#c8a24c',
                'phone' => null,
                'whatsapp' => null,
                'instagram_url' => null,
                'facebook_url' => null,
                'tiktok_url' => null,
                'google_maps_url' => null,
                'address_ar' => null,
                'address_en' => null,
                'working_hours' => [
                    ['label_en' => 'Saturday – Thursday', 'label_ar' => 'السبت – الخميس', 'hours' => '12:00 – 02:00'],
                    ['label_en' => 'Friday', 'label_ar' => 'الجمعة', 'hours' => '13:00 – 02:00'],
                ],
                'currency' => 'EGP',
                'default_locale' => 'ar',
            ]
        );

        RestaurantSetting::flushCache();
    }
}

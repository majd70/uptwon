<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\QrScan;
use App\Models\RestaurantSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RestaurantSettingSeeder::class);
    }

    private function makeMenu(): Category
    {
        $category = Category::create([
            'name_en' => 'Soups',
            'name_ar' => 'الشوربة',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        MenuItem::create([
            'category_id' => $category->id,
            'name_en' => 'Lentil Soup',
            'name_ar' => 'العدس',
            'description_en' => 'Lentils, tomatoes, garlic and cumin.',
            'description_ar' => 'عدس وطماطم وثوم وكمون.',
            'price' => 75,
            'sort_order' => 1,
        ]);

        // Mirrors the eight real items the source documents carry no price for.
        MenuItem::create([
            'category_id' => $category->id,
            'name_en' => 'Penne White',
            'name_ar' => 'بنا وايت',
            'price' => null,
            'sort_order' => 2,
        ]);

        return $category;
    }

    public function test_landing_page_loads(): void
    {
        // Default locale is Arabic, so the Arabic name is what renders.
        $this->get('/')
            ->assertOk()
            ->assertSee('أب تاون', false)
            ->assertSee(route('menu'));

        $this->get('/?lang=en')
            ->assertOk()
            ->assertSee('Uptown Restaurant &amp; Café', false);
    }

    public function test_menu_page_lists_imported_items(): void
    {
        $this->makeMenu();

        $this->get('/menu?lang=en')
            ->assertOk()
            ->assertSee('Soups')
            ->assertSee('Lentil Soup')
            ->assertSee('Lentils, tomatoes, garlic and cumin.');
    }

    public function test_items_without_a_price_render_a_dash_rather_than_zero(): void
    {
        $this->makeMenu();

        $response = $this->get('/menu?lang=en');

        $response->assertOk()->assertSee('Penne White');
        $this->assertStringContainsString('—', $response->getContent());
        $this->assertStringNotContainsString('Penne White</span>0', $response->getContent());
    }

    public function test_hidden_categories_and_empty_categories_are_not_shown(): void
    {
        $this->makeMenu();

        Category::create(['name_en' => 'Secret', 'name_ar' => 'سري', 'sort_order' => 2, 'is_visible' => false]);
        Category::create(['name_en' => 'Empty', 'name_ar' => 'فارغ', 'sort_order' => 3, 'is_visible' => true]);

        $this->get('/menu?lang=en')
            ->assertOk()
            ->assertDontSee('Secret')
            ->assertDontSee('Empty');
    }

    public function test_locale_switching_flips_language_and_direction(): void
    {
        $this->makeMenu();

        $en = $this->get('/menu?lang=en');
        $en->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee('Lentil Soup');

        $ar = $this->get('/menu?lang=ar');
        $ar->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('العدس', false);
    }

    public function test_locale_choice_persists_in_a_cookie(): void
    {
        $this->get('/?lang=en')->assertOk()->assertCookie('uptown_locale', 'en');

        $this->withCookie('uptown_locale', 'en')
            ->get('/')
            ->assertOk()
            ->assertSee('lang="en"', false);
    }

    public function test_locale_falls_back_to_the_configured_default(): void
    {
        RestaurantSetting::current()->update(['default_locale' => 'en']);
        RestaurantSetting::flushCache();

        $this->get('/')->assertOk()->assertSee('lang="en"', false);
    }

    public function test_names_fall_back_to_the_other_language_when_missing(): void
    {
        $category = $this->makeMenu();

        MenuItem::create([
            'category_id' => $category->id,
            'name_en' => 'Tres Leches',
            'name_ar' => null, // the Arabic PDF has no dessert section
            'price' => 210,
            'sort_order' => 3,
        ]);

        // Arabic page still shows the English name rather than an empty row.
        $this->get('/menu?lang=ar')->assertOk()->assertSee('Tres Leches');
    }

    public function test_qr_scan_is_logged_with_the_table_number(): void
    {
        $this->get('/?utm_source=qr&table=5')->assertOk();

        $this->assertSame(1, QrScan::count());
        $scan = QrScan::first();
        $this->assertSame('qr', $scan->utm_source);
        $this->assertSame('5', $scan->table_number);
    }

    public function test_plain_visits_are_not_logged_as_scans(): void
    {
        $this->get('/')->assertOk();

        $this->assertSame(0, QrScan::count());
    }

    public function test_monogram_is_used_when_no_logo_is_uploaded(): void
    {
        RestaurantSetting::current()->update(['monogram' => 'UT']);
        RestaurantSetting::flushCache();

        $this->get('/?lang=en')->assertOk()->assertSee('>UT<', false);
    }

    public function test_monogram_falls_back_to_the_name_initials(): void
    {
        RestaurantSetting::current()->update(['monogram' => null, 'name_en' => 'Golden Palm Bistro']);
        RestaurantSetting::flushCache();

        $this->assertSame('GP', RestaurantSetting::current()->monogramText());
    }

    public function test_a_custom_monogram_overrides_the_initials(): void
    {
        RestaurantSetting::current()->update(['monogram' => 'أت']);
        RestaurantSetting::flushCache();

        $this->assertSame('أت', RestaurantSetting::current()->monogramText());
    }

    /** Served by the web server straight from public/, so assert the file itself. */
    public function test_robots_txt_exists_and_blocks_the_admin(): void
    {
        $path = public_path('robots.txt');

        $this->assertFileExists($path);
        $this->assertStringContainsString('Disallow: /admin', file_get_contents($path));
    }
}

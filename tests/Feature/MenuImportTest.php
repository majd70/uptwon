<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MenuImportTest extends TestCase
{
    use RefreshDatabase;

    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RestaurantSettingSeeder::class);

        $this->fixture = storage_path('framework/testing/menu_data.json');
        File::ensureDirectoryExists(dirname($this->fixture));
        File::put($this->fixture, json_encode([
            'categories' => [
                ['name_en' => 'Soups', 'name_ar' => 'الشوربة', 'sort_order' => 1],
                ['name_en' => 'Desserts', 'name_ar' => 'الحلويات', 'sort_order' => 2],
            ],
            'items' => [
                [
                    'category_en' => 'Soups',
                    'name_en' => 'Lentil Soup',
                    'name_ar' => 'العدس',
                    'description_en' => 'Lentils, tomatoes and cumin.',
                    'description_ar' => 'عدس وطماطم وكمون.',
                    'price' => 75,
                    'image' => null,
                    'sort_order' => 1,
                    'flags' => [],
                ],
                [
                    'category_en' => 'Desserts',
                    'name_en' => 'Tres Leches',
                    'name_ar' => null,
                    'description_en' => null,
                    'description_ar' => null,
                    'price' => null,
                    'image' => null,
                    'sort_order' => 2,
                    'flags' => ['missing_price', 'missing_arabic'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE));
    }

    protected function tearDown(): void
    {
        File::delete($this->fixture);

        parent::tearDown();
    }

    private function import(): void
    {
        $this->artisan('menu:import', ['--file' => $this->fixture, '--skip-images' => true])
            ->assertExitCode(0);
    }

    public function test_it_imports_categories_and_items(): void
    {
        $this->import();

        $this->assertSame(2, Category::count());
        $this->assertSame(2, MenuItem::count());

        $soup = MenuItem::where('name_en', 'Lentil Soup')->firstOrFail();
        $this->assertSame('العدس', $soup->name_ar);
        $this->assertSame('75.00', $soup->price);
        $this->assertSame('Soups', $soup->category->name_en);
    }

    public function test_it_keeps_missing_prices_null_rather_than_zero(): void
    {
        $this->import();

        $this->assertNull(MenuItem::where('name_en', 'Tres Leches')->firstOrFail()->price);
    }

    public function test_it_is_idempotent(): void
    {
        $this->import();
        $firstIds = MenuItem::orderBy('id')->pluck('id')->all();

        $this->import();
        $this->import();

        $this->assertSame(2, Category::count(), 'Re-running duplicated categories.');
        $this->assertSame(2, MenuItem::count(), 'Re-running duplicated items.');
        $this->assertSame($firstIds, MenuItem::orderBy('id')->pluck('id')->all());
    }

    public function test_reimport_updates_changed_values_without_resetting_admin_choices(): void
    {
        $this->import();

        $item = MenuItem::where('name_en', 'Lentil Soup')->firstOrFail();
        $item->update(['is_available' => false, 'is_featured' => true]);

        $this->import();

        $item->refresh();
        $this->assertFalse($item->is_available, 'Re-import overwrote the availability toggle.');
        $this->assertTrue($item->is_featured, 'Re-import overwrote the featured toggle.');
    }

    public function test_it_fails_cleanly_when_the_data_file_is_missing(): void
    {
        $this->artisan('menu:import', ['--file' => storage_path('nope.json')])
            ->assertExitCode(1);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('menu:import', ['--file' => $this->fixture, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(0, Category::count());
        $this->assertSame(0, MenuItem::count());
    }
}

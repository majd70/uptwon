<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use App\Services\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imports menu_data.json (produced from the restaurant's DOCX/XLSX/PDF) into the
 * database.
 *
 * Idempotent: categories are keyed on name_en and items on (category, name_en),
 * so re-running updates rows in place rather than duplicating them. Images are
 * written to deterministic paths, so they are overwritten, not accumulated.
 */
class ImportMenu extends Command
{
    protected $signature = 'menu:import
        {--file= : Path to menu_data.json (defaults to ../import/, then database/data/)}
        {--skip-images : Import text only, leave existing images untouched}
        {--prune : Delete categories and items that are not in the file}
        {--dry-run : Report what would change without writing anything}';

    protected $description = 'Import categories, menu items and images from the bundled menu data file';

    /**
     * The bundled dataset ships inside the app so a deployed checkout can import
     * without the original source documents. The working copy beside the app
     * still wins when it is present.
     */
    private function defaultDataFile(): ?string
    {
        foreach ([base_path('../import/menu_data.json'), database_path('data/menu_data.json')] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function handle(ImageProcessor $images): int
    {
        $file = $this->option('file') ?: $this->defaultDataFile();

        if (! $file || ! is_file($file)) {
            $this->error('Data file not found.');
            $this->line('Expected database/data/menu_data.json inside the app, or ../import/menu_data.json beside it.');

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        if (empty($data['categories']) || empty($data['items'])) {
            $this->error('Data file has no categories or items.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        // Only present in the full working copy; a deployed checkout ships the
        // already-processed WebP files instead.
        $imageDir = dirname(realpath($file)).'/../item_images';

        if ($dryRun) {
            $this->warn('Dry run — no changes will be written.');
        }

        $stats = ['cat_new' => 0, 'cat_upd' => 0, 'new' => 0, 'upd' => 0, 'img' => 0, 'reused' => 0, 'noimg' => 0];

        $run = function () use ($data, $images, $imageDir, $dryRun, &$stats) {
            $categoryIds = [];

            foreach ($data['categories'] as $row) {
                $category = Category::firstOrNew(['name_en' => $row['name_en']]);
                $stats[$category->exists ? 'cat_upd' : 'cat_new']++;
                $category->fill([
                    'name_ar' => $row['name_ar'],
                    'sort_order' => $row['sort_order'],
                ]);
                // is_visible is an editorial choice made in the admin; only set it
                // on first import so re-running never un-hides a hidden category.
                if (! $category->exists) {
                    $category->is_visible = true;
                }
                if (! $dryRun) {
                    $category->save();
                }
                // null only for a category that does not exist yet during a dry
                // run, which by definition has no items in the database either
                $categoryIds[$row['name_en']] = $category->id;
            }

            foreach ($data['items'] as $row) {
                $categoryId = $categoryIds[$row['category_en']] ?? null;

                if (! $categoryId && ! $dryRun) {
                    $this->warn("Skipping '{$row['name_en']}': unknown category '{$row['category_en']}'");

                    continue;
                }

                $item = MenuItem::firstOrNew([
                    'category_id' => $categoryId,
                    'name_en' => $row['name_en'],
                ]);
                $isNew = ! $item->exists;
                $stats[$isNew ? 'new' : 'upd']++;

                $item->fill([
                    'name_ar' => $row['name_ar'] ?: null,
                    'description_en' => $row['description_en'] ?: null,
                    'description_ar' => $row['description_ar'] ?: null,
                    'price' => $row['price'],
                    'sort_order' => $row['sort_order'],
                    'tags' => $row['flags'] ?: null,
                ]);
                if ($isNew) {
                    $item->is_available = true;
                    $item->is_featured = false;
                }

                if (empty($row['image'])) {
                    $stats['noimg']++;
                } elseif (! $this->option('skip-images')) {
                    $source = $imageDir.'/'.$row['image'];
                    // Deterministic, so a checkout that already ships the
                    // processed file lands on exactly the same path.
                    $stored = 'menu-items/'.Str::slug($row['name_en']).'.webp';

                    if (is_file($source)) {
                        if (! $dryRun) {
                            $item->image = $images->storeFromPath($source, 'menu-items', $row['name_en']);
                        }
                        $stats['img']++;
                    } elseif (Storage::disk('public')->exists($stored)) {
                        // Deployed checkout: the original photographs are not in
                        // the repository but the WebP versions are.
                        if (! $dryRun) {
                            $item->image = $stored;
                        }
                        $stats['reused']++;
                    } else {
                        $this->warn("No image for '{$row['name_en']}' (looked for {$source} and {$stored})");
                        $stats['noimg']++;
                    }
                }

                if (! $dryRun) {
                    $item->save();
                }
            }

            if ($this->option('prune') && ! $dryRun) {
                $kept = collect($data['items'])->map(
                    fn ($r) => ($categoryIds[$r['category_en']] ?? 0).'|'.$r['name_en']
                )->all();

                $removed = MenuItem::all()
                    ->reject(fn ($i) => in_array($i->category_id.'|'.$i->name_en, $kept, true))
                    ->each->delete()
                    ->count();

                $prunedCategories = Category::whereNotIn('name_en', array_keys($categoryIds))->delete();
                $this->line("Pruned {$removed} items and {$prunedCategories} categories.");
            }
        };

        $dryRun ? $run() : DB::transaction($run);

        if (! $dryRun) {
            RestaurantSetting::flushCache();
        }

        $this->newLine();
        $this->info('Menu import complete.');
        $this->table(
            ['Categories +', 'Categories ~', 'Items +', 'Items ~', 'Images written', 'Images reused', 'No image'],
            [[$stats['cat_new'], $stats['cat_upd'], $stats['new'], $stats['upd'], $stats['img'], $stats['reused'], $stats['noimg']]],
        );

        if (! $dryRun && ($stats['img'] + $stats['reused']) > 0 && ! Storage::disk('public')->exists('menu-items')) {
            $this->warn('No images were written — run `php artisan storage:link` if the public disk is not linked.');
        }

        return self::SUCCESS;
    }
}

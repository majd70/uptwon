<?php

namespace Tests\Feature;

use App\Models\RestaurantSetting;
use App\Services\QrCodeBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RestaurantSettingSeeder::class);
    }

    public function test_the_dashboard_website_address_overrides_app_url(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        RestaurantSetting::current()->update(['site_url' => 'https://uptown-restaurant.com/']);
        RestaurantSetting::flushCache();

        // Trailing slash trimmed, and the QR follows the dashboard, not .env.
        $this->assertSame('https://uptown-restaurant.com', RestaurantSetting::current()->publicUrl());
        $this->assertStringStartsWith('https://uptown-restaurant.com/?', QrCodeBuilder::url());
    }

    public function test_it_falls_back_to_app_url_when_no_website_address_is_set(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        RestaurantSetting::current()->update(['site_url' => null]);
        RestaurantSetting::flushCache();

        $this->assertSame('http://127.0.0.1:8000', RestaurantSetting::current()->publicUrl());
        $this->assertStringStartsWith('http://127.0.0.1:8000/?', QrCodeBuilder::url());
    }

    public function test_target_url_carries_the_tracking_parameters(): void
    {
        $this->assertStringContainsString('utm_source=qr', QrCodeBuilder::url());
        $this->assertStringNotContainsString('table=', QrCodeBuilder::url());

        $withTable = QrCodeBuilder::url('7');
        $this->assertStringContainsString('utm_source=qr', $withTable);
        $this->assertStringContainsString('table=7', $withTable);
    }

    public function test_png_is_a_real_decodable_image(): void
    {
        $png = app(QrCodeBuilder::class)->png(null, 512);

        $this->assertNotEmpty($png);
        // PNG magic number
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));

        $info = getimagesizefromstring($png);
        $this->assertNotFalse($info, 'PNG payload could not be parsed as an image.');
        $this->assertSame(IMAGETYPE_PNG, $info[2]);
        $this->assertGreaterThan(100, $info[0]);
        $this->assertSame($info[0], $info[1], 'QR codes must be square.');
    }

    public function test_svg_is_well_formed(): void
    {
        $svg = app(QrCodeBuilder::class)->svg('3', 512);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
        $this->assertNotFalse(simplexml_load_string($svg), 'SVG payload is not valid XML.');
    }

    /**
     * Per-table codes are no longer offered in the admin, but the builder and
     * the qr_scans.table_number column still handle them, so a table parameter
     * must keep producing a distinct, valid code.
     */
    public function test_the_builder_still_supports_a_table_parameter(): void
    {
        $builder = app(QrCodeBuilder::class);

        $this->assertNotSame($builder->png(null, 256), $builder->png('12', 256));
    }
}

<?php

namespace Tests\Feature;

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

    public function test_table_codes_differ_from_the_general_code(): void
    {
        $builder = app(QrCodeBuilder::class);

        $this->assertNotSame($builder->png(null, 256), $builder->png('12', 256));
    }
}

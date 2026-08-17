<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RestaurantSettingSeeder::class);
    }

    public static function adminRoutes(): array
    {
        return [
            'dashboard' => ['/admin'],
            'categories' => ['/admin/categories'],
            'menu items' => ['/admin/menu-items'],
            'settings' => ['/admin/manage-settings'],
            'qr codes' => ['/admin/qr-codes'],
        ];
    }

    /**
     * @dataProvider adminRoutes
     */
    public function test_admin_requires_authentication(string $url): void
    {
        $this->get($url)->assertRedirect('/admin/login');
    }

    /**
     * @dataProvider adminRoutes
     */
    public function test_admin_pages_load_for_an_authenticated_user(string $url): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret-password',
        ]);

        $this->actingAs($user)->get($url)->assertOk();
    }

    public function test_login_page_is_public(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}

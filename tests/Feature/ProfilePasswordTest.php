<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(\Database\Seeders\RestaurantSettingSeeder::class);

        return User::create([
            'name' => 'Uptown Admin',
            'email' => 'admin@uptown.test',
            'password' => 'old-password-123',
        ]);
    }

    public function test_the_profile_page_requires_authentication(): void
    {
        $this->get('/admin/profile')->assertRedirect('/admin/login');
    }

    public function test_the_profile_page_loads_for_a_signed_in_user(): void
    {
        $this->actingAs($this->admin())->get('/admin/profile')->assertOk();
    }

    public function test_the_password_changes_when_the_current_one_is_correct(): void
    {
        $user = $this->admin();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'old-password-123',
                'password' => 'a-much-better-password',
                'passwordConfirmation' => 'a-much-better-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('a-much-better-password', $user->refresh()->password));
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $user = $this->admin();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'not-the-right-one',
                'password' => 'attacker-chosen-password',
                'passwordConfirmation' => 'attacker-chosen-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['current_password']);

        // The old password must still work.
        $this->assertTrue(Hash::check('old-password-123', $user->refresh()->password));
    }

    public function test_the_current_password_is_required_when_setting_a_new_one(): void
    {
        $user = $this->admin();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => null,
                'password' => 'trying-to-skip-the-check',
                'passwordConfirmation' => 'trying-to-skip-the-check',
            ])
            ->call('save')
            ->assertHasFormErrors(['current_password']);

        $this->assertTrue(Hash::check('old-password-123', $user->refresh()->password));
    }

    public function test_the_name_can_be_changed_without_touching_the_password(): void
    {
        $user = $this->admin();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'name' => 'Mahmoud',
                'email' => $user->email,
                'current_password' => null,
                'password' => null,
                'passwordConfirmation' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('Mahmoud', $user->name);
        $this->assertTrue(Hash::check('old-password-123', $user->password));
    }
}

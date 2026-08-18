<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

/**
 * Filament's stock profile page changes the password without asking for the
 * current one, so anyone who finds an unlocked session can lock the owner out
 * of the live menu. This adds that check — and only demands it when a new
 * password is actually being set, so editing a name stays friction-free.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Account')
                ->schema([
                    $this->getNameFormComponent(),
                    $this->getEmailFormComponent(),
                ]),

            Section::make('Change password')
                ->description('Leave these empty to keep the password you have now.')
                ->schema([
                    $this->getCurrentPasswordFormComponent(),
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                ]),
        ]);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('current_password')
            ->label('Current password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->currentPassword()
            ->requiredWith('password')
            // Never written to the model; it exists only to be verified.
            ->dehydrated(false)
            ->autocomplete('current-password');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('New password')
            ->minLength(8)
            ->helperText('At least 8 characters.');
    }
}

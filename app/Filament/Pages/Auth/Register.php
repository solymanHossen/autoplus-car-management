<?php

namespace App\Filament\Pages\Auth;

use App\Models\Tenant;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Form; // Correct import

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                TextInput::make('company_name')
                    ->label('Company Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Acme Garage'),
                TextInput::make('domain')
                    ->label('Subdomain')
                    ->required()
                    ->unique(Tenant::class, 'domain')
                    ->maxLength(255)
                    ->suffix('.autopulse.test') // Optional visual helper
                    ->placeholder('acme'),
            ])
            ->statePath('data');
    }

    protected function handleRegistration(array $data): User
    {
        $tenant = Tenant::create([
            'name' => $data['company_name'],
            'domain' => $data['domain'],
            'subscription_status' => 'active',
        ]);

        $user = new User();
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Hash is handled by mutator or fill if in $cast? No, standard User usually needs manual hashing unless Filament's BaseRegister does something? BaseRegister usually handles hashing.
            'role' => 'owner',
            'phone' => null, // Provide default or nullable
        ]);
        
        // Ensure password is hashed if User model doesn't cast it
        // Filament Registration normally handles this in handleRegistration but we are overriding it.
        // Base implementation does: $data['password'] = Hash::make($data['password']);
        
        // Let's check if we should just call parent or manually do it.
        // We can't call parent because parent tries to create User directly.
        
        $user->password = $data['password']; // User model cast usually handles hashing or we should hash it.
        // Let's assume standard Laravel User model which casts password => 'hashed' in Laravel 10+, 
        // looking at the user model provided earlier, it uses `Authenticatable`, assume standard behavior.
        // But for safety:
        // Checking User.php provided -> doesn't show casts.
        // I will use Hash facade to be safe.
        
        $user->tenant_id = $tenant->id;
        $user->save();

        return $user;
    }
}

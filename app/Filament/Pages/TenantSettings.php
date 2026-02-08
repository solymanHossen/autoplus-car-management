<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TenantSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Tenant Settings';

    protected static string $view = 'filament.pages.tenant-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('edit-settings') ?? false;
    }

    public function mount(): void
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $this->form->fill([
            'company_name' => $settings['company_name'] ?? '',
            'company_phone' => $settings['company_phone'] ?? '',
            'company_email' => $settings['company_email'] ?? '',
            'company_address' => $settings['company_address'] ?? '',
            'invoice_prefix' => $settings['invoice_prefix'] ?? 'INV-',
            'default_payment_terms' => $settings['default_payment_terms'] ?? '30',
            'invoice_footer_text' => $settings['invoice_footer_text'] ?? '',
            'default_tax_rate' => $settings['default_tax_rate'] ?? '0',
            'timezone' => $settings['timezone'] ?? 'UTC',
            'date_format' => $settings['date_format'] ?? 'Y-m-d',
            'currency' => $settings['currency'] ?? 'USD',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company_phone')
                            ->label('Phone')
                            ->tel(),
                        Forms\Components\TextInput::make('company_email')
                            ->label('Email')
                            ->email(),
                        Forms\Components\Textarea::make('company_address')
                            ->label('Address')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Invoice Settings')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_prefix')
                            ->label('Invoice Prefix'),
                        Forms\Components\TextInput::make('default_payment_terms')
                            ->label('Default Payment Terms (days)')
                            ->numeric(),
                        Forms\Components\Textarea::make('invoice_footer_text')
                            ->label('Invoice Footer Text')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Tax Settings')
                    ->schema([
                        Forms\Components\TextInput::make('default_tax_rate')
                            ->label('Default Tax Rate (%)')
                            ->numeric()
                            ->suffix('%'),
                    ]),

                Forms\Components\Section::make('Locale Settings')
                    ->schema([
                        Forms\Components\Select::make('timezone')
                            ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                            ->searchable(),
                        Forms\Components\Select::make('date_format')
                            ->options([
                                'Y-m-d' => 'YYYY-MM-DD',
                                'd/m/Y' => 'DD/MM/YYYY',
                                'm/d/Y' => 'MM/DD/YYYY',
                                'd-m-Y' => 'DD-MM-YYYY',
                            ]),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD ($)',
                                'EUR' => 'EUR (€)',
                                'GBP' => 'GBP (£)',
                                'BDT' => 'BDT (৳)',
                                'SAR' => 'SAR (﷼)',
                                'AED' => 'AED (د.إ)',
                            ]),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $tenantId = auth()->user()?->tenant_id;

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['value' => $value],
            );
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}

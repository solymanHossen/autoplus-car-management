<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\JobCardResource\Pages;
use App\Filament\Resources\JobCardResource\RelationManagers;
use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JobCardResource extends Resource
{
    protected static ?string $model = JobCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Service Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'job_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['job_number', 'customer.name', 'vehicle.registration_number'];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('view-job-cards') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Job Information')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('vehicle_id')
                            ->options(function (Get $get) {
                                $customerId = $get('customer_id');
                                if (! $customerId) {
                                    return [];
                                }

                                return Vehicle::where('customer_id', $customerId)
                                    ->pluck('registration_number', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->options(fn () => User::whereIn('role', ['mechanic', 'advisor'])->pluck('name', 'id')->toArray())
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'diagnosis' => 'Diagnosis',
                                'approval' => 'Approval',
                                'working' => 'Working',
                                'qc' => 'Quality Check',
                                'ready' => 'Ready',
                                'delivered' => 'Delivered',
                                'on_hold' => 'On Hold',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('normal')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Vehicle Condition')
                    ->schema([
                        Forms\Components\TextInput::make('mileage_in')
                            ->numeric()
                            ->suffix('km'),
                        Forms\Components\TextInput::make('mileage_out')
                            ->numeric()
                            ->suffix('km'),
                        Forms\Components\Textarea::make('customer_notes')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('diagnosis_notes')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('internal_notes')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn () => in_array(auth()->user()?->role, ['owner', 'manager', 'mechanic'])),
                    ])->columns(2),

                Forms\Components\Section::make('Timeline')
                    ->schema([
                        Forms\Components\DateTimePicker::make('estimated_completion'),
                        Forms\Components\DateTimePicker::make('actual_completion')
                            ->disabled()
                            ->visibleOn('edit'),
                    ])->columns(2),

                Forms\Components\Section::make('Parts & Services')
                    ->schema([
                        Forms\Components\Repeater::make('jobCardItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('item_type')
                                    ->options([
                                        'part' => 'Part',
                                        'service' => 'Service',
                                    ])
                                    ->required()
                                    ->live(),
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0),
                                Forms\Components\TextInput::make('discount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),
                                Forms\Components\TextInput::make('total')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled(),
                                Forms\Components\Textarea::make('notes')
                                    ->rows(1),
                            ])
                            ->columns(4)
                            ->columnSpanFull()
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('job_number')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.registration_number')
                    ->label('Vehicle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'diagnosis' => 'info',
                        'approval' => 'warning',
                        'working' => 'primary',
                        'qc' => 'info',
                        'ready' => 'success',
                        'delivered' => 'gray',
                        'on_hold' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_completion')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'pending' => 'Pending',
                        'diagnosis' => 'Diagnosis',
                        'approval' => 'Approval',
                        'working' => 'Working',
                        'qc' => 'Quality Check',
                        'ready' => 'Ready',
                        'delivered' => 'Delivered',
                        'on_hold' => 'On Hold',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->multiple()
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Assigned To'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasPermission('edit-job-cards')),
                Tables\Actions\Action::make('generateInvoice')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn (JobCard $record) => ! $record->invoice && auth()->user()?->hasPermission('create-invoices'))
                    ->action(function (JobCard $record) {
                        $invoice = $record->invoice()->create([
                            'tenant_id' => $record->tenant_id,
                            'invoice_number' => 'INV-'.str_pad((string) (\App\Models\Invoice::count() + 1), 6, '0', STR_PAD_LEFT),
                            'customer_id' => $record->customer_id,
                            'invoice_date' => now(),
                            'due_date' => now()->addDays(30),
                            'status' => 'draft',
                            'subtotal' => $record->subtotal ?? 0,
                            'tax_amount' => $record->tax_amount ?? 0,
                            'discount_amount' => $record->discount_amount ?? 0,
                            'total_amount' => $record->total_amount ?? 0,
                            'paid_amount' => 0,
                            'balance' => $record->total_amount ?? 0,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Invoice Generated')
                            ->body("Invoice {$invoice->invoice_number} created successfully.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasPermission('delete-job-cards')),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Job Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('job_number')->weight('bold'),
                        Infolists\Components\TextEntry::make('customer.name'),
                        Infolists\Components\TextEntry::make('vehicle.registration_number')->label('Vehicle'),
                        Infolists\Components\TextEntry::make('assignedTo.name')->label('Assigned To'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'diagnosis' => 'info',
                                'working' => 'primary',
                                'ready' => 'success',
                                'delivered' => 'gray',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('priority')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'low' => 'gray',
                                'normal' => 'info',
                                'high' => 'warning',
                                'urgent' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(3),

                Infolists\Components\Section::make('Vehicle Condition')
                    ->schema([
                        Infolists\Components\TextEntry::make('mileage_in')->suffix(' km'),
                        Infolists\Components\TextEntry::make('mileage_out')->suffix(' km'),
                        Infolists\Components\TextEntry::make('customer_notes'),
                        Infolists\Components\TextEntry::make('diagnosis_notes'),
                        Infolists\Components\TextEntry::make('internal_notes')
                            ->visible(fn () => in_array(auth()->user()?->role, ['owner', 'manager', 'mechanic'])),
                    ])->columns(2),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('estimated_completion')->dateTime(),
                        Infolists\Components\TextEntry::make('actual_completion')->dateTime(),
                        Infolists\Components\TextEntry::make('started_at')->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')->dateTime(),
                        Infolists\Components\TextEntry::make('delivered_at')->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Financial Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')->money('USD'),
                        Infolists\Components\TextEntry::make('tax_amount')->money('USD'),
                        Infolists\Components\TextEntry::make('discount_amount')->money('USD'),
                        Infolists\Components\TextEntry::make('total_amount')->money('USD')->weight('bold'),
                    ])->columns(4),

                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')->dateTime(),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\JobCardItemsRelationManager::class,
            RelationManagers\AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobCards::route('/'),
            'create' => Pages\CreateJobCard::route('/create'),
            'view' => Pages\ViewJobCard::route('/{record}'),
            'edit' => Pages\EditJobCard::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if (auth()->user()?->role === 'mechanic') {
            $query->where('assigned_to', auth()->id());
        }

        return $query;
    }
}

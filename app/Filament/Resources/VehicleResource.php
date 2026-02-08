<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Customer Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'registration_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['registration_number', 'make', 'model', 'vin'];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('view-vehicles') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vehicle Details')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('email')->email(),
                                Forms\Components\TextInput::make('phone')->tel(),
                            ]),
                        Forms\Components\TextInput::make('registration_number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('make')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('model')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('year')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(now()->year + 1),
                        Forms\Components\TextInput::make('color')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vin')
                            ->label('VIN')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('engine_number')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Mileage & Service')
                    ->schema([
                        Forms\Components\TextInput::make('current_mileage')
                            ->numeric()
                            ->suffix('km'),
                        Forms\Components\DatePicker::make('last_service_date'),
                        Forms\Components\DatePicker::make('next_service_date'),
                        Forms\Components\DatePicker::make('purchase_date'),
                    ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('registration_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('make')
                    ->sortable(),
                Tables\Columns\TextColumn::make('model'),
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('color'),
                Tables\Columns\TextColumn::make('current_mileage')
                    ->numeric()
                    ->suffix(' km'),
                Tables\Columns\TextColumn::make('next_service_date')
                    ->date()
                    ->color(function ($state) {
                        if (! $state) {
                            return null;
                        }
                        $date = \Carbon\Carbon::parse($state);
                        if ($date->isPast()) {
                            return 'danger';
                        }
                        if ($date->diffInDays(now()) <= 7) {
                            return 'warning';
                        }

                        return null;
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('make')
                    ->options(fn () => Vehicle::distinct()->whereNotNull('make')->pluck('make', 'make')->toArray()),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Customer'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasPermission('delete-vehicles')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasPermission('delete-vehicles')),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\JobCardsRelationManager::class,
            RelationManagers\ServiceRemindersRelationManager::class,
            RelationManagers\AppointmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku'];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('view-inventory') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Details')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\KeyValue::make('name_local')
                            ->label('Localized Name')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'part' => 'Part',
                                'service' => 'Service',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('category')
                            ->maxLength(255),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('cost_price')
                            ->numeric()
                            ->prefix('$'),
                    ])->columns(2),

                Forms\Components\Section::make('Stock')
                    ->schema([
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('min_stock_level')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'part' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->color(fn (Product $record) => $record->stock_quantity <= $record->min_stock_level ? 'danger' : null)
                    ->weight(fn (Product $record) => $record->stock_quantity <= $record->min_stock_level ? 'bold' : null),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'part' => 'Part',
                        'service' => 'Service',
                    ]),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock_quantity', '<=', 'min_stock_level')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasPermission('edit-inventory')),
                Tables\Actions\Action::make('adjustStock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->hasPermission('adjust-inventory'))
                    ->form([
                        Forms\Components\Select::make('transaction_type')
                            ->options([
                                'purchase' => 'Purchase',
                                'adjustment' => 'Adjustment',
                                'return' => 'Return',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\TextInput::make('unit_cost')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(2),
                    ])
                    ->action(function (Product $record, array $data) {
                        $record->inventoryTransactions()->create([
                            'tenant_id' => $record->tenant_id,
                            'transaction_type' => $data['transaction_type'],
                            'quantity' => $data['quantity'],
                            'unit_price' => $data['unit_cost'] ?? 0,
                            'total_amount' => ($data['unit_cost'] ?? 0) * $data['quantity'],
                            'notes' => $data['notes'] ?? null,
                            'performed_by' => auth()->id(),
                        ]);

                        $record->increment('stock_quantity', (int) $data['quantity']);

                        \Filament\Notifications\Notification::make()
                            ->title('Stock Adjusted')
                            ->body("Added {$data['quantity']} units to {$record->name}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasPermission('delete-inventory')),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InventoryTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
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

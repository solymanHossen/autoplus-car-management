<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobCardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class JobCardItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'jobCardItems';

    protected static ?string $title = 'Parts & Services';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_type')
                    ->options([
                        'part' => 'Part',
                        'service' => 'Service',
                    ])
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->default(1)
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
                    ->prefix('$'),
                Forms\Components\Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'part' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product/Service'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('tax_rate')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('discount')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->weight('bold'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

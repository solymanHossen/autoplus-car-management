<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock'),
            ])
            ->defaultSort('name');
    }
}

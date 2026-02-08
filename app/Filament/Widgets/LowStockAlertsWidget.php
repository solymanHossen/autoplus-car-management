<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = 'Low Stock Alerts';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('type', 'part')
                    ->whereColumn('stock_quantity', '<=', 'min_stock_level')
                    ->orderBy('stock_quantity')
            )
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Current Stock')
                    ->color('danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('min_stock_level')
                    ->label('Min Level'),
            ]);
    }

    public static function canView(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, ['owner', 'manager']);
    }
}

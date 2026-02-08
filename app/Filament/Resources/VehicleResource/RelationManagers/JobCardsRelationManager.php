<?php

declare(strict_types=1);

namespace App\Filament\Resources\VehicleResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class JobCardsRelationManager extends RelationManager
{
    protected static string $relationship = 'jobCards';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('job_number')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
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
                Tables\Columns\TextColumn::make('priority')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

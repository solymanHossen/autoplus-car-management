<?php

declare(strict_types=1);

namespace App\Filament\Resources\VehicleResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('appointment_date')
                    ->date(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('service_type'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'in_progress' => 'primary',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('appointment_date', 'desc');
    }
}

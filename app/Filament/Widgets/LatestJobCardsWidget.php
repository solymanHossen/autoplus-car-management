<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\JobCard;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestJobCardsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Latest Job Cards';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = JobCard::query()->with(['customer', 'vehicle', 'assignedTo'])->latest();

        if (auth()->user()?->role === 'mechanic') {
            $query->where('assigned_to', auth()->id());
        }

        return $table
            ->query($query)
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('job_number')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.registration_number')
                    ->label('Vehicle'),
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
                    ->label('Mechanic'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->label('Total'),
            ]);
    }

    public static function canView(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, ['owner', 'manager', 'advisor', 'mechanic']);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingAppointmentsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Upcoming Appointments';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->with(['customer', 'vehicle'])
                    ->where('appointment_date', '>=', today())
                    ->orderBy('appointment_date')
                    ->orderBy('start_time')
            )
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.registration_number')
                    ->label('Vehicle'),
                Tables\Columns\TextColumn::make('appointment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service'),
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
            ]);
    }

    public static function canView(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, ['owner', 'manager', 'advisor']);
    }
}

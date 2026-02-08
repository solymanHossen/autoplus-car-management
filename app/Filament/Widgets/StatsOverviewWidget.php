<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobCard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $role = $user?->role;

        if (in_array($role, ['mechanic'])) {
            return $this->getMechanicStats();
        }

        if ($role === 'accountant') {
            return $this->getAccountantStats();
        }

        return $this->getFullStats();
    }

    protected function getFullStats(): array
    {
        return [
            Stat::make('Total Customers', Customer::count())
                ->description('All registered customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Active Job Cards', JobCard::whereNotIn('status', ['delivered', 'cancelled'])->count())
                ->description('In progress')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),
            Stat::make('Pending Invoice Balance', '$' . number_format((float) Invoice::whereIn('status', ['sent', 'overdue', 'partially_paid'])->sum('balance'), 2))
                ->description('Outstanding amount')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
            Stat::make("Today's Appointments", Appointment::whereDate('appointment_date', today())->count())
                ->description('Scheduled for today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
        ];
    }

    protected function getMechanicStats(): array
    {
        $userId = auth()->id();

        return [
            Stat::make('My Active Jobs', JobCard::where('assigned_to', $userId)->whereNotIn('status', ['delivered', 'cancelled'])->count())
                ->description('Assigned to me')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),
            Stat::make('Completed Today', JobCard::where('assigned_to', $userId)->whereDate('completed_at', today())->count())
                ->description('Jobs completed today')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }

    protected function getAccountantStats(): array
    {
        return [
            Stat::make('Pending Invoice Balance', '$' . number_format((float) Invoice::whereIn('status', ['sent', 'overdue', 'partially_paid'])->sum('balance'), 2))
                ->description('Outstanding amount')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
            Stat::make('Overdue Invoices', Invoice::where('status', 'overdue')->count())
                ->description('Past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
            Stat::make('Payments This Month', '$' . number_format((float) \App\Models\Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'), 2))
                ->description('Collected this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}

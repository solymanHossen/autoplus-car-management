<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Payment;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.pages.reports';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('view-reports') ?? false;
    }

    public function getRevenueData(): array
    {
        $payments = Payment::selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
            ->whereYear('payment_date', now()->year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return $payments;
    }

    public function getJobCardStats(): array
    {
        return [
            'total' => JobCard::count(),
            'completed' => JobCard::where('status', 'delivered')->count(),
            'in_progress' => JobCard::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'cancelled' => JobCard::where('status', 'cancelled')->count(),
        ];
    }

    public function getFinancialSummary(): array
    {
        return [
            'total_revenue' => (float) Payment::whereYear('payment_date', now()->year)->sum('amount'),
            'total_expenses' => (float) Expense::whereYear('expense_date', now()->year)->sum('amount'),
            'outstanding_invoices' => (float) Invoice::whereIn('status', ['sent', 'overdue', 'partially_paid'])->sum('balance'),
        ];
    }
}

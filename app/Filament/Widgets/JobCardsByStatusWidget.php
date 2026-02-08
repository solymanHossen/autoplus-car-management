<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\JobCard;
use Filament\Widgets\ChartWidget;

class JobCardsByStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Job Cards by Status';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $statuses = ['pending', 'diagnosis', 'approval', 'working', 'qc', 'ready', 'delivered', 'on_hold', 'cancelled'];
        $colors = [
            'pending' => '#eab308',
            'diagnosis' => '#3b82f6',
            'approval' => '#f97316',
            'working' => '#6366f1',
            'qc' => '#a855f7',
            'ready' => '#22c55e',
            'delivered' => '#6b7280',
            'on_hold' => '#78716c',
            'cancelled' => '#ef4444',
        ];

        $counts = [];
        $bgColors = [];
        $labels = [];

        foreach ($statuses as $status) {
            $count = JobCard::where('status', $status)->count();
            if ($count > 0) {
                $counts[] = $count;
                $bgColors[] = $colors[$status] ?? '#6b7280';
                $labels[] = ucfirst(str_replace('_', ' ', $status));
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $counts,
                    'backgroundColor' => $bgColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, ['owner', 'manager', 'advisor']);
    }
}

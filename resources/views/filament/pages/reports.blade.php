<x-filament-panels::page>
    <div class="grid gap-6 md:grid-cols-3">
        @php
            $financial = $this->getFinancialSummary();
            $jobCardStats = $this->getJobCardStats();
        @endphp

        <x-filament::section>
            <x-slot name="heading">Revenue (This Year)</x-slot>
            <p class="text-2xl font-bold text-primary-600">${{ number_format($financial['total_revenue'], 2) }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Expenses (This Year)</x-slot>
            <p class="text-2xl font-bold text-danger-600">${{ number_format($financial['total_expenses'], 2) }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Outstanding Invoices</x-slot>
            <p class="text-2xl font-bold text-warning-600">${{ number_format($financial['outstanding_invoices'], 2) }}</p>
        </x-filament::section>
    </div>

    <div class="grid gap-6 mt-6 md:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Job Card Summary</x-slot>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Total</dt>
                    <dd class="text-lg font-semibold">{{ $jobCardStats['total'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Completed</dt>
                    <dd class="text-lg font-semibold text-success-600">{{ $jobCardStats['completed'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">In Progress</dt>
                    <dd class="text-lg font-semibold text-primary-600">{{ $jobCardStats['in_progress'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Cancelled</dt>
                    <dd class="text-lg font-semibold text-danger-600">{{ $jobCardStats['cancelled'] }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Monthly Revenue Breakdown</x-slot>
            @php $revenueData = $this->getRevenueData(); @endphp
            <dl class="space-y-2">
                @foreach(range(1, 12) as $month)
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ \Carbon\Carbon::create()->month($month)->format('F') }}</dt>
                        <dd class="text-sm font-medium">${{ number_format($revenueData[$month] ?? 0, 2) }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>

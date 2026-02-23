<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobCard;
use Illuminate\Http\JsonResponse;

/**
 * Dashboard API Controller
 */
class DashboardController extends ApiController
{
    /**
     * Return tenant-scoped dashboard statistics.
     */
    public function stats(): JsonResponse
    {
        $activeJobCardStatuses = [
            'pending',
            'diagnosis',
            'approval',
            'working',
            'qc',
            'ready',
            'on_hold',
        ];

        $stats = [
            'total_customers' => Customer::query()->count(),
            'active_job_cards' => JobCard::query()->whereIn('status', $activeJobCardStatuses)->count(),
            'pending_invoices' => Invoice::query()->where('balance', '>', 0)->count(),
            'today_appointments' => Appointment::query()->whereDate('appointment_date', today())->count(),
        ];

        return $this->successResponse(
            $stats,
            'Dashboard statistics retrieved successfully'
        );
    }
}

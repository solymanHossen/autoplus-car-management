<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Invoice API Controller
 */
class InvoiceController extends ApiController
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $this->resolvePerPage($request);

            $invoices = QueryBuilder::for(Invoice::class)
                ->allowedFilters(['status', 'customer_id', 'job_card_id', 'invoice_number'])
                ->allowedSorts(['invoice_number', 'invoice_date', 'due_date', 'total_amount', 'created_at'])
                ->allowedIncludes(['customer', 'jobCard', 'payments'])
                ->paginate($perPage)
                ->appends($request->query());

            return $this->paginatedResponse(
                $invoices,
                InvoiceResource::class,
                'Invoices retrieved successfully'
            );
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to retrieve invoices', 500);
        }
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = DB::transaction(function () use ($request) {
                $data = $request->validated();
                $tenantId = (string) auth()->user()->tenant_id;

                $data['tenant_id'] = $tenantId;
                $data['invoice_number'] = $this->generateInvoiceNumber($tenantId);
                $data['paid_amount'] = $data['paid_amount'] ?? 0;
                $data['balance'] = $data['total_amount'] - ($data['paid_amount'] ?? 0);

                return Invoice::create($data);
            });

            $invoice->load(['customer', 'jobCard']);

            return $this->successResponse(
                new InvoiceResource($invoice),
                'Invoice created successfully',
                201
            );
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create invoice', 500);
        }
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        try {
            $invoice->load(['customer', 'jobCard', 'payments']);

            return $this->successResponse(
                new InvoiceResource($invoice),
                'Invoice retrieved successfully'
            );
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to retrieve invoice', 500);
        }
    }

    /**
     * Update the specified invoice.
     */
    public function update(StoreInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $invoice): void {
                $data = $request->validated();
                $data['balance'] = $data['total_amount'] - $invoice->paid_amount;

                $invoice->update($data);
            });

            return $this->successResponse(
                new InvoiceResource($invoice->fresh()->load(['customer', 'jobCard'])),
                'Invoice updated successfully'
            );
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update invoice', 500);
        }
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        try {
            $invoice->delete();

            return $this->successResponse(
                null,
                'Invoice deleted successfully'
            );
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete invoice', 500);
        }
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(string $tenantId): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');

        $lastInvoice = Invoice::where('tenant_id', $tenantId)
            ->where('invoice_number', 'LIKE', "{$prefix}-{$year}{$month}%")
            ->orderBy('invoice_number', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$year}{$month}{$newNumber}";
    }
}

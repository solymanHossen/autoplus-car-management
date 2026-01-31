<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Payment API Controller
 */
class PaymentController extends ApiController
{
    /**
     * Display a listing of payments.
     */
    public function index(): JsonResponse
    {
        try {
            $payments = QueryBuilder::for(Payment::class)
                ->allowedFilters(['invoice_id', 'payment_method', 'received_by'])
                ->allowedSorts(['payment_date', 'amount', 'created_at'])
                ->allowedIncludes(['invoice', 'receivedBy'])
                ->paginate(15);

            return $this->paginatedResponse(
                $payments,
                PaymentResource::class,
                'Payments retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payments: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created payment.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tenant_id'] = auth()->user()->tenant_id;
            $data['received_by'] = $data['received_by'] ?? auth()->id();

            $payment = Payment::create($data);

            // Update invoice paid amount and balance
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $invoice->paid_amount += $payment->amount;
            $invoice->balance = $invoice->total_amount - $invoice->paid_amount;
            
            // Update invoice status based on payment
            if ($invoice->balance <= 0) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partially_paid';
            }
            
            $invoice->save();

            $payment->load(['invoice', 'receivedBy']);

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment recorded successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        try {
            $payment->load(['invoice', 'receivedBy']);
            
            return $this->successResponse(
                new PaymentResource($payment),
                'Payment retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified payment.
     */
    public function update(StorePaymentRequest $request, Payment $payment): JsonResponse
    {
        try {
            $oldAmount = $payment->amount;
            $newAmount = $request->validated()['amount'];
            
            $payment->update($request->validated());

            // Adjust invoice amounts
            $invoice = $payment->invoice;
            $invoice->paid_amount = $invoice->paid_amount - $oldAmount + $newAmount;
            $invoice->balance = $invoice->total_amount - $invoice->paid_amount;
            
            // Update invoice status
            if ($invoice->balance <= 0) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partially_paid';
            } else {
                $invoice->status = 'sent';
            }
            
            $invoice->save();

            return $this->successResponse(
                new PaymentResource($payment->fresh()->load(['invoice', 'receivedBy'])),
                'Payment updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        try {
            // Adjust invoice amounts before deleting payment
            $invoice = $payment->invoice;
            $invoice->paid_amount -= $payment->amount;
            $invoice->balance = $invoice->total_amount - $invoice->paid_amount;
            
            // Update invoice status
            if ($invoice->paid_amount <= 0) {
                $invoice->status = 'sent';
            } elseif ($invoice->balance > 0) {
                $invoice->status = 'partially_paid';
            }
            
            $invoice->save();

            $payment->delete();

            return $this->successResponse(
                null,
                'Payment deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete payment: ' . $e->getMessage(), 500);
        }
    }
}

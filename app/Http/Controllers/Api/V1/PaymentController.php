<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Payment API Controller
 */
class PaymentController extends ApiController
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $this->resolvePerPage($request);

            $payments = QueryBuilder::for(Payment::class)
                ->allowedFilters(['invoice_id', 'payment_method', 'received_by'])
                ->allowedSorts(['payment_date', 'amount', 'created_at'])
                ->allowedIncludes(['invoice', 'receivedBy'])
                ->paginate($perPage)
                ->appends($request->query());

            return $this->paginatedResponse(
                $payments,
                PaymentResource::class,
                'Payments retrieved successfully'
            );
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to retrieve payments', 500);
        }
    }

    /**
     * Store a newly created payment.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = DB::transaction(function () use ($request) {
                $data = $request->validated();
                $data['tenant_id'] = auth()->user()->tenant_id;
                $data['received_by'] = $data['received_by'] ?? auth()->id();

                // Update invoice paid amount and balance atomically
                $invoice = Invoice::whereKey($data['invoice_id'])->lockForUpdate()->firstOrFail();

                // Security: Prevent over-collection under concurrent requests.
                if ((float) $data['amount'] > (float) $invoice->balance) {
                    throw ValidationException::withMessages([
                        'amount' => [
                            sprintf('Payment amount cannot exceed remaining invoice balance (%.2f).', (float) $invoice->balance),
                        ],
                    ]);
                }

                $payment = Payment::create($data);

                $invoice->paid_amount += $payment->amount;
                $invoice->balance = $invoice->total_amount - $invoice->paid_amount;

                // Update invoice status based on payment
                if ($invoice->balance <= 0) {
                    $invoice->status = 'paid';
                } elseif ($invoice->paid_amount > 0) {
                    $invoice->status = 'partially_paid';
                }

                $invoice->save();

                return $payment;
            });

            $payment->load(['invoice', 'receivedBy']);

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment recorded successfully',
                201
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to record payment', 500);
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
            report($e);

            return $this->errorResponse('Failed to retrieve payment', 500);
        }
    }

    /**
     * Update the specified payment.
     */
    public function update(StorePaymentRequest $request, Payment $payment): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $payment): void {
                $oldAmount = $payment->amount;
                $newAmount = $request->validated()['amount'];

                $payment->update($request->validated());

                // Adjust invoice amounts atomically
                $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
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
            });

            return $this->successResponse(
                new PaymentResource($payment->fresh()->load(['invoice', 'receivedBy'])),
                'Payment updated successfully'
            );
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update payment', 500);
        }
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        try {
            DB::transaction(function () use ($payment): void {
                // Adjust invoice amounts before deleting payment
                $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
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
            });

            return $this->successResponse(
                null,
                'Payment deleted successfully'
            );
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete payment', 500);
        }
    }
}

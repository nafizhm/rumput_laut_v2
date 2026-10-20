<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreInvoicePaymentRequest;
use App\Models\InvoicePayment;
use App\Services\Sales\InvoicePaymentService;
use App\Traits\JsonResponser;
use Exception;

class InvoicePaymentController extends Controller
{
    use JsonResponser;

    public function __construct(protected InvoicePaymentService $service) {}

    public function store(StoreInvoicePaymentRequest $request)
    {
        try {
            $payment = $this->service->recordPayment($request->validated());

            return $this->successResponse('Pembayaran invoice berhasil dicatat', $payment);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy(InvoicePayment $invoice_payment)
    {
        $this->service->deletePayment($invoice_payment);

        return $this->successResponse('Pencairan invoice beserta potongan kerja sama berhasil dibatalkan');
    }
}

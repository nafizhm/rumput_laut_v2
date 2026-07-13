<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreFarmerPaymentRequest;
use App\Models\FarmerPayment;
use App\Services\Transaction\FarmerPaymentService;
use App\Traits\JsonResponser;
use Exception;

class FarmerPaymentController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected FarmerPaymentService $service
    ) {}

    public function store(StoreFarmerPaymentRequest $request)
    {
        try {
            $this->service->createPayment($request->validated());
            return $this->successResponse('Pembayaran hutang berhasil dicatat');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy(FarmerPayment $farmer_payment)
    {
        try {
            $this->service->deletePayment($farmer_payment);
            return $this->successResponse('Pembayaran hutang berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Exceptions\PaymentVerificationFailedException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\VerifyBillPaymentRequest;
use App\Http\Resources\Api\V1\BillResource;
use App\Models\Tenant\MaintenanceBill;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Starts and confirms a resident's online payment for one of their own
 * bills. All the "can this be trusted" logic lives in RazorpayService —
 * this controller only resolves the bill (scoped to the caller's own
 * flats, same as BillController::show()), calls the service, and shapes
 * the HTTP response.
 */
class BillPaymentController extends ApiController
{
    public function __construct(private readonly RazorpayService $razorpay) {}

    /**
     * Creates a Razorpay order for the bill's current balance. The request
     * carries no amount — there is nothing here for a client to tamper
     * with; see RazorpayService::createOrderForBill().
     */
    public function createOrder(Request $request, int $id): JsonResponse
    {
        $bill = MaintenanceBill::whereIn('flat_id', $this->myFlatIds())->findOrFail($id);

        try {
            $order = $this->razorpay->createOrderForBill($bill, $this->user());
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (PaymentVerificationFailedException $e) {
            Log::warning('bill_payment.create_order_failed', ['bill_id' => $id, 'reason' => $e->getMessage()]);

            return $this->fail($e->getSafeMessage(), 502);
        }

        $society = $request->attributes->get('api_society');

        return $this->ok([
            'razorpay_order_id' => $order->razorpay_order_id,
            'amount' => (int) round($order->amount * 100),
            'currency' => $order->currency,
            'key' => config('services.razorpay.key'),
            'name' => $society?->name ?? 'FlatCare',
            'description' => $bill->title,
            'bill_id' => $bill->id,
            'prefill' => [
                'name' => $this->user()->name,
                'email' => $this->user()->email,
                'contact' => $this->user()->phone,
            ],
        ]);
    }

    /**
     * Confirms a checkout-completion claim from the app. Never trusts it on
     * its own — see RazorpayService::verifyAndRecordPayment() for the full
     * chain of checks. Only ever returns a generic message on failure; the
     * real reason is logged server-side, never shown to the client.
     */
    public function verify(VerifyBillPaymentRequest $request, int $id): JsonResponse
    {
        try {
            $bill = $this->razorpay->verifyAndRecordPayment(
                billId: $id,
                flatIds: $this->myFlatIds(),
                orderId: $request->string('razorpay_order_id')->value(),
                paymentId: $request->string('razorpay_payment_id')->value(),
                signature: $request->string('razorpay_signature')->value(),
                user: $this->user(),
            );
        } catch (PaymentVerificationFailedException $e) {
            Log::warning('bill_payment.verify_failed', ['bill_id' => $id, 'reason' => $e->getMessage()]);

            return $this->fail($e->getSafeMessage(), 422);
        }

        return $this->ok(new BillResource($bill), 'Payment verified successfully.');
    }
}

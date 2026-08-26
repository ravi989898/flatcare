<?php

namespace App\Http\Requests\Api\V1\Resident;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cheap fail-fast shape validation on the {order_id, payment_id, signature}
 * triple the app hands back after checkout — before any of it touches the
 * database or a Razorpay API call. This is defense-in-depth only: the real
 * trust decision happens in RazorpayService::verifyAndRecordPayment().
 */
class VerifyBillPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'razorpay_order_id' => ['required', 'string', 'starts_with:order_'],
            'razorpay_payment_id' => ['required', 'string', 'starts_with:pay_'],
            'razorpay_signature' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
        ];
    }
}

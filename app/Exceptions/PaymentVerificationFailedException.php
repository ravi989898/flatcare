<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by RazorpayService whenever a payment can't be verified as
 * genuine — bad signature, order already consumed, Razorpay reports
 * anything other than "captured", an amount/order mismatch, etc.
 *
 * The constructor takes two separate messages on purpose: `$logMessage`
 * (the real, specific reason) is never shown to the client — only ever
 * logged server-side — because a raw reason (e.g. "duplicate
 * razorpay_payment_id") would hand an attacker a working oracle for
 * iterating toward an actual replay/forgery. `$safeMessage` is the one
 * generic string BillPaymentController is allowed to return in the API
 * response.
 */
class PaymentVerificationFailedException extends Exception
{
    public function __construct(
        string $logMessage,
        private readonly string $safeMessage = 'We could not verify this payment. If money was deducted, it will be reconciled shortly — contact support if it is not reflected within a day.',
    ) {
        parent::__construct($logMessage);
    }

    public function getSafeMessage(): string
    {
        return $this->safeMessage;
    }
}

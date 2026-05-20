<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Payment extends Entity
{
    protected array $_accessible = [
        'booking_id' => true,
        'amount' => true,
        'currency_code' => true,
        'payment_date' => true,
        'payment_method' => true,
        'payment_status' => true,
        'transaction_reference' => true,
        'stripe_session_id' => true,
        'stripe_payment_intent_id' => true,
        'stripe_charge_id' => true,
        'stripe_customer_id' => true,
        'stripe_invoice_id' => true,
        'stripe_invoice_pdf_url' => true,
        'stripe_receipt_url' => true,
        'stripe_payment_method_type' => true,
        'refunded_amount' => true,
        'notes' => true,
        'booking' => true,
        'payment_refunds' => true,
        'payment_disputes' => true,
        'payment_profile' => true,
    ];
}

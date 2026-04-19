<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\StripeCheckoutSessionClassifier;
use Cake\TestSuite\TestCase;

class StripeCheckoutSessionClassifierTest extends TestCase
{
    private StripeCheckoutSessionClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new StripeCheckoutSessionClassifier();
    }

    public function testPaidSessionIsClassifiedAsPaid(): void
    {
        $result = $this->classifier->classify($this->makeSession('complete', 'paid'));

        $this->assertSame(StripeCheckoutSessionClassifier::STATE_PAID, $result['state']);
    }

    public function testCompleteButUnpaidSessionIsAwaitingPayment(): void
    {
        $result = $this->classifier->classify($this->makeSession('complete', 'unpaid'));

        $this->assertSame(StripeCheckoutSessionClassifier::STATE_AWAITING_PAYMENT, $result['state']);
    }

    public function testOpenNonPaidSessionIsReusable(): void
    {
        $result = $this->classifier->classify($this->makeSession('open', 'no_payment_required'));

        $this->assertSame(StripeCheckoutSessionClassifier::STATE_OPEN_NON_PAID, $result['state']);
    }

    public function testOpenSessionWithUnknownPaymentStatusGetsDedicatedState(): void
    {
        $result = $this->classifier->classify($this->makeSession('open', 'processing'));

        $this->assertSame(StripeCheckoutSessionClassifier::STATE_OPEN_UNKNOWN_PAYMENT_STATUS, $result['state']);
    }

    public function testExpiredSessionIsExpired(): void
    {
        $result = $this->classifier->classify($this->makeSession('expired', 'unpaid'));

        $this->assertSame(StripeCheckoutSessionClassifier::STATE_EXPIRED, $result['state']);
    }

    public function testUnknownStateFallsBackToStale(): void
    {
        $result = $this->classifier->classify($this->makeSession('unknown', 'unpaid'));

        $this->assertSame(StripeCheckoutSessionClassifier::STATE_STALE, $result['state']);
    }

    private function makeSession(string $status, string $paymentStatus): object
    {
        return (object)[
            'status' => $status,
            'payment_status' => $paymentStatus,
        ];
    }
}

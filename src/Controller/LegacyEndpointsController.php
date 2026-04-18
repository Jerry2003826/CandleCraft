<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;

class LegacyEndpointsController extends Controller
{
    public function removedStripeWebhook(): Response
    {
        $this->request->allowMethod(['post']);

        return $this->response
            ->withStatus(410)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'error' => 'Stripe webhook endpoint has moved to /stripe/webhook.',
            ]));
    }
}

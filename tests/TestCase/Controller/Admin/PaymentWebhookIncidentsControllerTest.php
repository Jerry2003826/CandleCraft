<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class PaymentWebhookIncidentsControllerTest extends AppIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $incidentsTable = FactoryLocator::get('Table')->get('PaymentWebhookIncidents');
        $incident = $incidentsTable->newEntity([
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_admin_fixture',
            'payment_id' => 1,
            'booking_id' => 1,
            'reason_code' => 'payment_not_found',
            'severity' => 'error',
            'status' => 'open',
            'context_json' => '{"reason_code":"payment_not_found"}',
            'payload_hash' => hash('sha256', 'admin-fixture'),
            'notes' => 'Fixture incident',
        ]);
        $incidentsTable->saveOrFail($incident);
    }

    public function testAdminCanViewIncidentIndex(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/payment-webhook-incidents');

        $this->assertResponseOk();
        $this->assertResponseContains('Webhook Incidents');
        $this->assertResponseContains('payment_not_found');
        $this->assertResponseContains('cs_admin_fixture');
    }

    public function testStudentCannotAccessIncidentIndex(): void
    {
        $this->loginAsStudent();

        $this->get('/admin/payment-webhook-incidents');

        $this->assertRedirectContains('/login');
    }

    public function testAdminCanResolveIncident(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $incidentId = (int)FactoryLocator::get('Table')->get('PaymentWebhookIncidents')
            ->find()
            ->where(['session_id' => 'cs_admin_fixture'])
            ->firstOrFail()
            ->incident_id;

        $this->post('/admin/payment-webhook-incidents/resolve/' . $incidentId, []);

        $this->assertRedirectContains('/admin/payment-webhook-incidents');

        $incident = FactoryLocator::get('Table')->get('PaymentWebhookIncidents')->get($incidentId);
        $this->assertSame('resolved', $incident->status);
        $this->assertNotNull($incident->resolved_at);
        $this->assertSame(1, (int)$incident->resolved_by_admin_id);
    }

    public function testAdminCanIgnoreIncident(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $incidentId = (int)FactoryLocator::get('Table')->get('PaymentWebhookIncidents')
            ->find()
            ->where(['session_id' => 'cs_admin_fixture'])
            ->firstOrFail()
            ->incident_id;

        $this->post('/admin/payment-webhook-incidents/ignore/' . $incidentId, []);

        $this->assertRedirectContains('/admin/payment-webhook-incidents');

        $incident = FactoryLocator::get('Table')->get('PaymentWebhookIncidents')->get($incidentId);
        $this->assertSame('ignored', $incident->status);
        $this->assertNotNull($incident->resolved_at);
        $this->assertSame(1, (int)$incident->resolved_by_admin_id);
    }
}

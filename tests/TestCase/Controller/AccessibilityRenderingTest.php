<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

class AccessibilityRenderingTest extends AppIntegrationTestCase
{
    public function testHomePageRendersSkipLinkAndAccessibleCourseDisclosure(): void
    {
        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseContains('href="#main-content"');
        $this->assertResponseContains('id="main-content"');
        $this->assertResponseContains('aria-controls="home-courses-menu"');
        $this->assertResponseContains('class="nav-dropdown__toggle"');
    }

    public function testContactPageRendersLabelsAndExpandableAccountRequestControls(): void
    {
        $this->get('/contact');

        $this->assertResponseOk();
        $this->assertResponseContains('for="sender-name"');
        $this->assertResponseContains('for="sender-email"');
        $this->assertResponseContains('aria-controls="request-account-fields"');
        $this->assertResponseContains('aria-expanded="false"');
        $this->assertResponseContains('data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"');
    }

    public function testContactPageInvalidSubmitRendersAccessibleErrorFeedback(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/contact', [
            'source_page' => 'homepage',
            'form_type' => 'enquiry',
            'sender_name' => 'Test User',
            'sender_email' => 'test@example.com',
            'sender_phone' => '0400000000',
            'subject' => 'General enquiry',
            'message_text' => 'Please contact me about upcoming pottery classes.',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('role="alert"');
        $this->assertResponseContains('id="captcha-error"');
    }

    public function testLoginPageRendersProgrammaticLabels(): void
    {
        $this->get('/login');

        $this->assertResponseOk();
        $this->assertResponseContains('for="email"');
        $this->assertResponseContains('for="password"');
        $this->assertResponseContains('autocomplete="current-password"');
    }

    public function testAdminDashboardRendersAccessibleSidebarAndThemeControls(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/dashboard');

        $this->assertResponseOk();
        $this->assertResponseContains('id="themeToggle"');
        $this->assertResponseContains('aria-pressed="false"');
        $this->assertResponseContains('aria-controls="adminSidebar"');
        $this->assertResponseContains('aria-label="Open admin navigation"');
        $this->assertResponseContains('<span>Logout</span>');
        $this->assertResponseNotContains('&lt;i class=&quot;bi bi-box-arrow-right&quot; aria-hidden=&quot;true&quot;&gt;&lt;/i&gt;');
        $this->assertResponseNotContains('settingsPlaceholder');
    }

    public function testConsumerDashboardRendersUnescapedLogoutButtonMarkup(): void
    {
        $this->loginAsStudent();
        $this->get('/consumer/dashboard');

        $this->assertResponseOk();
        $this->assertResponseContains('<span>Logout</span>');
        $this->assertResponseNotContains('&lt;i class=&quot;bi bi-box-arrow-right&quot; aria-hidden=&quot;true&quot;&gt;&lt;/i&gt;');
    }

    public function testAdminCoursesRemovesDeadViewToggleAndLabelsActions(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/courses');

        $this->assertResponseOk();
        $this->assertResponseContains('id="courseSearch"');
        $this->assertResponseContains('aria-label="Search courses"');
        $this->assertResponseContains('aria-label="Edit ');
        $this->assertResponseNotContains('href="#"');
        $this->assertResponseNotContains('gridViewBtn');
    }

    public function testConsumerBookingsRendersAccessibleToggleButtonsAndWeekNavigation(): void
    {
        $this->loginAsStudent();
        $this->get('/consumer/bookings');

        $this->assertResponseOk();
        $this->assertResponseContains('data-view-toggle-managed="custom"');
        $this->assertResponseContains('aria-controls="calendarView"');
        $this->assertResponseContains('aria-controls="listView"');
        $this->assertResponseContains('aria-label="Show previous week"');
        $this->assertResponseNotContains('href="#"');
    }

    public function testConsumerCoursesRendersAccessibleToggleButtonsAndPanels(): void
    {
        $this->loginAsStudent();
        $this->get('/consumer/courses');

        $this->assertResponseOk();
        $this->assertResponseContains('data-view-toggle-managed="custom"');
        $this->assertResponseContains('aria-controls="listView"');
        $this->assertResponseContains('id="calendarNav"');
        $this->assertResponseContains('id="listNav"');
        $this->assertResponseNotContains('href="#"');
    }

    public function testConsumerPaymentsRendersButtonTabsAndLabeledBillingFields(): void
    {
        $this->loginAsStudent();
        $this->get('/consumer/payments');

        $this->assertResponseOk();
        $this->assertResponseContains('data-tab="overview"');
        $this->assertResponseContains('aria-controls="details"');
        $this->assertResponseContains('data-tab-trigger="details"');
        $this->assertResponseContains('for="billing-name"');
        $this->assertResponseContains('for="billing-email"');
    }
}

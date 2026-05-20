<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;

class AccessibilityRenderingTest extends AppIntegrationTestCase
{
    private const CMS_TEST_PAGE_SLUG = 'accessibility-contact-test';

    protected function tearDown(): void
    {
        $sitePages = FactoryLocator::get('Table')->get('SitePages');
        $pageSections = FactoryLocator::get('Table')->get('PageSections');
        $pageIds = $sitePages->find()
            ->select(['id'])
            ->where(['page_slug' => self::CMS_TEST_PAGE_SLUG])
            ->all()
            ->extract('id')
            ->toList();

        if ($pageIds !== []) {
            $pageSections->deleteAll(['page_id IN' => $pageIds]);
            $sitePages->deleteAll(['id IN' => $pageIds]);
        }

        Configure::delete('Recaptcha.site_key');
        Configure::delete('Recaptcha.secret_key');

        parent::tearDown();
    }

    public function testHomePageRendersSkipLinkAndAccessibleCoursesNavLink(): void
    {
        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseContains('href="#main-content"');
        $this->assertResponseContains('id="main-content"');
        $this->assertResponseContains('class="hero-nav__courses"');
        $this->assertResponseContains('hero-nav--sticky');
    }

    public function testContactPageRendersLabelsAndExpandableAccountRequestControls(): void
    {
        $this->get('/contact');

        $this->assertResponseOk();
        $this->assertResponseContains('for="sender-name"');
        $this->assertResponseContains('for="sender-email"');
        $this->assertResponseContains('aria-controls="request-account-fields"');
        $this->assertResponseContains('aria-expanded="false"');
        $this->assertResponseContains('id="captcha-label"');
        $this->assertResponseContains('id="captcha-help"');
        $this->assertResponseContains('enquiry-field--subject');
    }

    public function testContactPageInvalidSubmitRendersAccessibleErrorFeedback(): void
    {
        Configure::write('Recaptcha.site_key', 'live-site-key');
        Configure::write('Recaptcha.secret_key', 'live-secret-key');
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
            'g-recaptcha-response' => '',
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
        $this->assertResponseContains('Recent Booking Activity');
        $this->assertResponseContains('Shows the latest booking records only');
        $this->assertResponseNotContains('&lt;i class=&quot;bi bi-box-arrow-right&quot; aria-hidden=&quot;true&quot;&gt;&lt;/i&gt;');
        $this->assertResponseNotContains('settingsPlaceholder');
    }

    public function testAdminCmsUsesPlainPageAddressTerminology(): void
    {
        $this->loginAsAdmin();
        $sitePages = FactoryLocator::get('Table')->get('SitePages');
        $pageSections = FactoryLocator::get('Table')->get('PageSections');
        $page = $sitePages->newEntity([
            'page_slug' => self::CMS_TEST_PAGE_SLUG,
            'page_title' => 'Contact / Enquiry',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => '2026-05-18 09:00:00',
            'updated_at' => '2026-05-18 09:00:00',
        ]);
        $sitePages->saveOrFail($page);
        $pageSections->saveOrFail($pageSections->newEntity([
            'page_id' => $page->id,
            'section_key' => 'intro.title',
            'section_label' => 'Intro title',
            'content_type' => 'text',
            'content_value' => 'Enquiry Form',
            'sort_order' => 1,
            'is_active' => true,
            'updated_at' => '2026-05-18 09:00:00',
        ]));

        $this->get('/admin/cms');

        $this->assertResponseOk();
        $this->assertResponseContains('Page URL');
        $this->assertResponseNotContains('<th>Slug</th>');

        $this->get('/admin/cms/pages/' . self::CMS_TEST_PAGE_SLUG);

        $this->assertResponseOk();
        $this->assertResponseContains('Page URL');
        $this->assertResponseNotContains('Slug:');
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

<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeedDefaultCmsContent extends BaseMigration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $pageRows = [
            ['page_slug' => 'global',  'page_title' => 'Global / Brand',     'sort_order' => 0],
            ['page_slug' => 'home',    'page_title' => 'Home Page',          'sort_order' => 1],
            ['page_slug' => 'contact', 'page_title' => 'Contact / Enquiry',  'sort_order' => 2],
            ['page_slug' => 'courses', 'page_title' => 'Courses Listing',    'sort_order' => 3],
        ];
        foreach ($pageRows as &$row) {
            $row['is_active'] = 1;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        $this->table('site_pages')->insert($pageRows)->save();

        $pageIdBySlug = [];
        $rows = $this->fetchAll('SELECT id, page_slug FROM site_pages');
        foreach ($rows as $r) {
            $pageIdBySlug[$r['page_slug']] = (int)$r['id'];
        }

        $sections = [];

        $globalSections = [
            ['branding.site_name',       'Site Name',           'Used in <title> tags and brand mark across the site.', 'text',     'CandleCraft Academy', 1],
            ['branding.site_subtitle',   'Site Subtitle',       'Tagline shown under the brand name in the public nav.', 'text',     'Pottery & Knitting Tutoring', 2],
            ['branding.copyright_text',  'Footer Copyright',    'Use {year} for the current year (e.g. "© {year} ...").', 'text',     '© {year} CandleCraft Academy. All rights reserved.', 3],
            ['branding.logo_image',      'Logo Image',          'Optional. Replaces the text brand mark when set.',     'image',    null, 4],
            ['branding.favicon_image',   'Favicon',             'Optional override for the browser tab icon.',          'image',    null, 5],
            ['nav.cta_label',            'Nav CTA Label',       'Public navigation enquiry button label.',              'text',     'Enquire', 6],
            ['nav.login_label',          'Nav Login Label',     'Public navigation log-in link label.',                 'text',     'Log In', 7],
        ];
        foreach ($globalSections as [$key, $label, $hint, $type, $value, $order]) {
            $sections[] = $this->section($pageIdBySlug['global'], $key, $label, $hint, $type, $value, $order, $now);
        }

        $homeSections = [
            ['hero.eyebrow',              'Hero Eyebrow',        'Small overline above the hero title.',           'text',     'A Sanctuary For The Creative Soul.', 1],
            ['hero.title',                'Hero Title',          'Main headline on the home page.',                'text',     'CandleCraft Academy', 2],
            ['hero.background_image',     'Hero Background',     'Full-bleed image behind the hero copy.',         'image',    null, 3],
            ['hero.cta_primary_label',    'Hero Primary Button', 'Label for the "Browse Courses" call-to-action.', 'text',     'Browse Courses', 4],
            ['hero.cta_secondary_label',  'Hero Secondary Button', 'Label for the "Enquire Today" call-to-action.', 'text',    'Enquire Today', 5],
        ];
        foreach ($homeSections as [$key, $label, $hint, $type, $value, $order]) {
            $sections[] = $this->section($pageIdBySlug['home'], $key, $label, $hint, $type, $value, $order, $now);
        }

        $contactSections = [
            ['intro.title', 'Page Title',     'Heading shown on the enquiry page.',          'text',     'Enquiry Form', 1],
            ['intro.body',  'Intro Paragraph', 'Short paragraph above the form.',            'textarea', 'Use the enquiry form below and someone from our team will be in touch shortly.', 2],
        ];
        foreach ($contactSections as [$key, $label, $hint, $type, $value, $order]) {
            $sections[] = $this->section($pageIdBySlug['contact'], $key, $label, $hint, $type, $value, $order, $now);
        }

        $potteryDesc = 'Learn pottery through guided, hands-on lessons that build your skills from basic techniques to creating your own finished pieces.';
        $knittingDesc = 'Learn knitting step by step with practical lessons that help you master stitches and create your own handmade projects.';
        $coursesSections = [
            ['intro.eyebrow',                 'Eyebrow Label',        'Small overline above the page title.', 'text', 'CandleCraft Academy', 1],
            ['intro.title',                   'Page Title',           'Main heading for the courses landing.', 'text', 'Our Courses', 2],
            ['category.pottery_title',        'Pottery Card Title',   'Heading on the Pottery category card.', 'text', 'Pottery', 3],
            ['category.pottery_description',  'Pottery Description',  'Short copy on the Pottery card.',       'html', $potteryDesc, 4],
            ['category.knitting_title',       'Knitting Card Title',  'Heading on the Knitting category card.', 'text', 'Knitting', 5],
            ['category.knitting_description', 'Knitting Description', 'Short copy on the Knitting card.',       'html', $knittingDesc, 6],
        ];
        foreach ($coursesSections as [$key, $label, $hint, $type, $value, $order]) {
            $sections[] = $this->section($pageIdBySlug['courses'], $key, $label, $hint, $type, $value, $order, $now);
        }

        $this->table('page_sections')->insert($sections)->save();
    }

    public function down(): void
    {
        $this->execute('DELETE FROM page_sections');
        $this->execute('DELETE FROM site_pages');
    }

    private function section(int $pageId, string $key, string $label, string $hint, string $type, ?string $value, int $order, string $now): array
    {
        return [
            'page_id'       => $pageId,
            'section_key'   => $key,
            'section_label' => $label,
            'section_hint'  => $hint,
            'content_type'  => $type,
            'content_value' => $value,
            'media_id'      => null,
            'is_active'     => 1,
            'sort_order'    => $order,
            'updated_at'    => $now,
            'updated_by_id' => null,
        ];
    }
}

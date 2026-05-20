<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Cms;

use App\Service\Cms\RevisionRecorder;
use Cake\TestSuite\TestCase;
use RuntimeException;

class RevisionRecorderTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.SitePages',
        'app.SiteMedia',
        'app.PageSections',
        'app.PageSectionRevisions',
        'app.PageSectionLocks',
    ];

    private RevisionRecorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recorder = new RevisionRecorder();
    }

    public function testSnapshotStoresCurrentValue(): void
    {
        $sections = $this->fetchTable('PageSections');
        $section = $sections->get(4);

        $revision = $this->recorder->snapshot($section, 1, 'Manual edit');

        $this->assertNotNull($revision->get('id'));
        $this->assertSame('CandleCraft Academy', $revision->get('content_value_snapshot'));
        $this->assertSame(1, (int)$revision->get('changed_by_id'));
        $this->assertSame('Manual edit', $revision->get('change_summary'));
    }

    public function testRestoreUnknownRevisionThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->recorder->restore(4, 9999, 1);
    }

    public function testRestoreOverwritesSectionAndAppendsRevision(): void
    {
        $sections = $this->fetchTable('PageSections');
        $section = $sections->get(4);
        $section->set('content_value', 'New Title');
        $sections->save($section);

        $restored = $this->recorder->restore(4, 1, 1);
        $this->assertSame('Old Title', $restored->get('content_value'));

        $rev = $this->fetchTable('PageSectionRevisions')->find()
            ->where(['section_id' => 4])
            ->orderBy(['id' => 'DESC'])
            ->first();
        $this->assertStringContainsString('Restored revision', (string)$rev->get('change_summary'));
    }
}

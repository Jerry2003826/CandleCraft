<?php
declare(strict_types=1);

namespace App\Service\Cms;

use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use RuntimeException;

/**
 * Append-only audit + restore for `page_sections`.
 *
 * - snapshot($section, $userId, $summary) writes the *current* value into
 *   `page_section_revisions` so the next mutation is reversible.
 * - restore($sectionId, $revisionId, $userId) writes a fresh snapshot first
 *   (so the timeline keeps growing append-only), then patches the section
 *   back to the chosen revision's snapshot, and returns the updated entity.
 */
final class RevisionRecorder
{
    use LocatorAwareTrait;

    /**
     * Snapshot.
     *
     * @param mixed $section Section.
     * @param mixed $userId Userid.
     * @param mixed $summary Summary.
     */
    public function snapshot(EntityInterface $section, ?int $userId, string $summary): EntityInterface
    {
        $entity = $this->revisions()->newEntity([
            'section_id' => (int)$section->get('id'),
            'content_value_snapshot' => $section->get('content_value'),
            'media_id_snapshot' => $section->get('media_id'),
            'changed_by_id' => $userId,
            'changed_at' => DateTime::now(),
            'change_summary' => $summary,
        ]);
        $this->revisions()->saveOrFail($entity);

        return $entity;
    }

    /**
     * Restore.
     *
     * @param mixed $sectionId Sectionid.
     * @param mixed $revisionId Revisionid.
     * @param mixed $userId Userid.
     */
    public function restore(int $sectionId, int $revisionId, ?int $userId): EntityInterface
    {
        $sections = $this->sections();
        $revisions = $this->revisions();

        $revision = $revisions->find()->where([
            'id' => $revisionId,
            'section_id' => $sectionId,
        ])->first();

        if ($revision === null) {
            throw new RuntimeException(sprintf(
                'Revision %d for section %d not found.',
                $revisionId,
                $sectionId,
            ));
        }

        $section = $sections->get($sectionId);

        $this->snapshot($section, $userId, sprintf(
            'Auto-snapshot before restoring revision #%d',
            $revisionId,
        ));

        $section->set('content_value', $revision->get('content_value_snapshot'));
        $section->set('media_id', $revision->get('media_id_snapshot'));
        $section->set('updated_at', DateTime::now());
        $section->set('updated_by_id', $userId);
        $sections->saveOrFail($section);

        $this->revisions()->saveOrFail($this->revisions()->newEntity([
            'section_id' => $sectionId,
            'content_value_snapshot' => $section->get('content_value'),
            'media_id_snapshot' => $section->get('media_id'),
            'changed_by_id' => $userId,
            'changed_at' => DateTime::now(),
            'change_summary' => 'Restored revision #' . $revisionId,
        ]));

        return $section;
    }

    /**
     * Sections.
     */
    private function sections(): Table
    {
        return $this->fetchTable('PageSections');
    }

    /**
     * Revisions.
     */
    private function revisions(): Table
    {
        return $this->fetchTable('PageSectionRevisions');
    }
}

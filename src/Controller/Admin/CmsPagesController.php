<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Cms\ContentResolver;
use App\Service\Cms\HtmlSanitizer;
use App\Service\Cms\LockOutcome;
use App\Service\Cms\MediaUploader;
use App\Service\Cms\MediaUploadException;
use App\Service\Cms\RevisionRecorder;
use App\Service\Cms\SectionLockService;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use RuntimeException;
use Throwable;

class CmsPagesController extends AppController
{
    private ContentResolver $resolver;
    private SectionLockService $lockService;
    private RevisionRecorder $recorder;
    private HtmlSanitizer $sanitizer;

    /**
     * Before filter.
     *
     * @param mixed $event Event.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->resolver = new ContentResolver();
        $this->lockService = new SectionLockService();
        $this->recorder = new RevisionRecorder();
        $this->sanitizer = new HtmlSanitizer();
    }

    /**
     * Current user id.
     */
    private function currentUserId(): int
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            throw new RuntimeException('Authenticated identity required.');
        }

        $userId = $identity->get('user_id');
        if ($userId === null && method_exists($identity, 'getIdentifier')) {
            $userId = $identity->getIdentifier();
        }

        return (int)$userId;
    }

    /**
     * Index.
     */
    public function index(): void
    {
        $sitePages = $this->fetchTable('SitePages');
        $pages = $sitePages->find()
            ->orderBy(['SitePages.sort_order' => 'ASC', 'SitePages.id' => 'ASC'])
            ->all();

        $sectionCounts = $this->fetchTable('PageSections')->find()
            ->select(['page_id', 'cnt' => 'COUNT(*)'])
            ->groupBy(['page_id'])
            ->disableHydration()
            ->all()
            ->combine('page_id', 'cnt')
            ->toArray();

        $this->set(compact('pages', 'sectionCounts'));
    }

    /**
     * View.
     *
     * @param mixed $slug Slug.
     */
    public function view(string $slug): void
    {
        $sitePages = $this->fetchTable('SitePages');
        $page = $sitePages->find()->where(['page_slug' => $slug])->first();
        if ($page === null) {
            throw new NotFoundException(sprintf('Unknown CMS page "%s".', $slug));
        }

        $sections = $this->fetchTable('PageSections')->find()
            ->contain(['SiteMedia', 'PageSectionLock' => ['LockedBy']])
            ->where(['PageSections.page_id' => (int)$page->get('id')])
            ->orderBy(['PageSections.sort_order' => 'ASC'])
            ->all();

        $now = DateTime::now();
        $this->set(compact('page', 'sections', 'now'));
    }

    /**
     * Edit section.
     *
     * @param mixed $id Id.
     */
    public function editSection(int $id): ?Response
    {
        $sectionsTable = $this->fetchTable('PageSections');
        $section = $sectionsTable->find()
            ->contain(['SitePages', 'SiteMedia'])
            ->where(['PageSections.id' => $id])
            ->first();
        if ($section === null) {
            throw new NotFoundException(sprintf('Section %d not found.', $id));
        }

        $userId = $this->currentUserId();

        if ($this->request->is(['post', 'put', 'patch'])) {
            return $this->handleSectionSave($section, $userId);
        }

        $lockOutcome = $this->lockService->acquire($id, $userId);

        $mediaOptions = $this->fetchTable('SiteMedia')->find()
            ->orderBy(['updated_at' => 'DESC'])
            ->all();

        $page = $section->get('site_page');
        $this->set(compact('section', 'page', 'lockOutcome', 'mediaOptions'));

        return null;
    }

    /**
     * Handle section save.
     *
     * @param mixed $section Section.
     * @param mixed $userId Userid.
     */
    private function handleSectionSave(EntityInterface $section, int $userId): Response
    {
        $sectionId = (int)$section->get('id');
        $page = $section->get('site_page');
        $pageSlug = $page?->get('page_slug') ?? '';

        $holder = $this->lockService->currentHolder($sectionId);
        if ($holder !== null && $holder->holderId !== $userId) {
            $this->Flash->error(sprintf(
                'This section is now being edited by %s. Your changes were not saved.',
                $holder->holderName ?? 'user #' . $holder->holderId,
            ));

            return $this->redirect(['action' => 'view', $pageSlug]);
        }

        $data = (array)$this->request->getData();
        $contentType = (string)$section->get('content_type');

        $sectionsTable = $this->fetchTable('PageSections');
        $sectionsTable->getConnection()->begin();
        try {
            $this->recorder->snapshot($section, $userId, 'Manual edit');

            if ($contentType === 'image') {
                $mediaId = $this->resolveMediaIdFromRequest($data, $userId);
                $section->set('media_id', $mediaId);
                $section->set('content_value', null);
            } else {
                $rawValue = (string)($data['content_value'] ?? '');
                if ($contentType === 'html') {
                    $rawValue = $this->sanitizer->clean($rawValue);
                }
                $section->set('content_value', $rawValue);
                $section->set('media_id', null);
            }

            $section->set('updated_at', DateTime::now());
            $section->set('updated_by_id', $userId);

            if (!$sectionsTable->save($section)) {
                $sectionsTable->getConnection()->rollback();
                $this->Flash->error('Could not save the section. Please review the errors and try again.');
                $lockOutcome = $this->lockService->currentHolder($sectionId)
                    ?? new LockOutcome(LockOutcome::ACQUIRED, holderId: $userId);
                $mediaOptions = $this->fetchTable('SiteMedia')->find()->orderBy(['updated_at' => 'DESC'])->all();
                $this->set(compact('section', 'page', 'lockOutcome', 'mediaOptions'));
                $this->render('edit_section');

                return $this->response;
            }

            $sectionsTable->getConnection()->commit();
        } catch (MediaUploadException $e) {
            $sectionsTable->getConnection()->rollback();
            $this->Flash->error($e->getMessage());

            return $this->redirect(['action' => 'editSection', $sectionId]);
        } catch (Throwable $e) {
            $sectionsTable->getConnection()->rollback();
            $this->Flash->error('An unexpected error occurred: ' . $e->getMessage());

            return $this->redirect(['action' => 'editSection', $sectionId]);
        }

        $this->resolver->invalidate($pageSlug);
        $this->lockService->release($sectionId, $userId);

        $this->Flash->success('Section updated.');

        return $this->redirect(['action' => 'view', $pageSlug]);
    }

    /**
     * Resolve media id from request.
     *
     * @param mixed $data Data.
     * @param mixed $userId Userid.
     */
    private function resolveMediaIdFromRequest(array $data, int $userId): ?int
    {
        $uploaded = $this->request->getUploadedFile('uploaded_image');
        if ($uploaded !== null && $uploaded->getError() === UPLOAD_ERR_OK && $uploaded->getSize() > 0) {
            $uploader = new MediaUploader();
            $alt = trim((string)($data['alt_text'] ?? '')) ?: null;
            $media = $uploader->store($uploaded, $alt, $userId);

            return (int)$media->get('id');
        }

        if (!empty($data['media_id'])) {
            return (int)$data['media_id'];
        }

        return null;
    }

    /**
     * History.
     *
     * @param mixed $id Id.
     */
    public function history(int $id): void
    {
        $sectionsTable = $this->fetchTable('PageSections');
        $section = $sectionsTable->find()
            ->contain(['SitePages'])
            ->where(['PageSections.id' => $id])
            ->first();
        if ($section === null) {
            throw new NotFoundException(sprintf('Section %d not found.', $id));
        }

        $revisions = $this->fetchTable('PageSectionRevisions')->find()
            ->contain(['ChangedBy'])
            ->where(['section_id' => $id])
            ->orderBy(['changed_at' => 'DESC', 'id' => 'DESC'])
            ->all();

        $this->set(compact('section', 'revisions'));
    }

    /**
     * Restore.
     *
     * @param mixed $id Id.
     * @param mixed $revisionId Revisionid.
     */
    public function restore(int $id, int $revisionId): Response
    {
        $this->request->allowMethod(['post']);
        $userId = $this->currentUserId();

        $sectionsTable = $this->fetchTable('PageSections');
        $sectionsTable->getConnection()->begin();
        try {
            $this->recorder->restore($id, $revisionId, $userId);
            $sectionsTable->getConnection()->commit();
        } catch (RuntimeException $e) {
            $sectionsTable->getConnection()->rollback();
            $this->Flash->error($e->getMessage());

            return $this->redirect(['action' => 'history', $id]);
        }

        $page = $sectionsTable->find()
            ->contain(['SitePages'])
            ->where(['PageSections.id' => $id])
            ->first()
            ?->get('site_page');

        $pageSlug = $page?->get('page_slug') ?? '';
        $this->resolver->invalidate($pageSlug);

        $this->Flash->success(sprintf('Restored revision #%d.', $revisionId));

        return $this->redirect(['action' => 'view', $pageSlug]);
    }

    /**
     * Heartbeat.
     *
     * @param mixed $id Id.
     */
    public function heartbeat(int $id): Response
    {
        $this->request->allowMethod(['post']);
        $userId = $this->currentUserId();
        $extended = $this->lockService->heartbeat($id, $userId);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'ok' => $extended,
            ]));
    }

    /**
     * Force lock.
     *
     * @param mixed $id Id.
     */
    public function forceLock(int $id): Response
    {
        $this->request->allowMethod(['post']);
        $userId = $this->currentUserId();

        $section = $this->fetchTable('PageSections')->find()
            ->where(['id' => $id])
            ->first();
        if ($section === null) {
            throw new NotFoundException(sprintf('Section %d not found.', $id));
        }

        $previous = $this->lockService->currentHolder($id);
        $this->lockService->forceTake($id, $userId);

        $summary = 'Lock force-taken';
        if ($previous !== null) {
            $summary .= ' from ' . ($previous->holderName ?? 'user #' . $previous->holderId);
        }
        $this->recorder->snapshot($section, $userId, $summary);

        return $this->redirect(['action' => 'editSection', $id]);
    }
}

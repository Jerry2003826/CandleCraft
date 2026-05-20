<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Cms\MediaUploader;
use App\Service\Cms\MediaUploadException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use RuntimeException;

class CmsMediaController extends AppController
{
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
        $media = $this->fetchTable('SiteMedia')->find()
            ->orderBy(['updated_at' => 'DESC', 'id' => 'DESC'])
            ->all();

        $this->set('media', $media);
    }

    /**
     * Upload.
     */
    public function upload(): Response
    {
        $this->request->allowMethod(['post']);
        $userId = $this->currentUserId();

        $uploaded = $this->request->getUploadedFile('file');
        $alt = trim((string)$this->request->getData('alt_text', '')) ?: null;

        if ($uploaded === null) {
            $this->Flash->error('Please choose a file before uploading.');

            return $this->redirect(['action' => 'index']);
        }

        try {
            $uploader = new MediaUploader();
            $uploader->store($uploaded, $alt, $userId);
            $this->Flash->success('Media uploaded.');
        } catch (MediaUploadException $e) {
            $this->Flash->error('Upload rejected: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Delete.
     *
     * @param mixed $id Id.
     */
    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post']);

        $mediaTable = $this->fetchTable('SiteMedia');
        $media = $mediaTable->find()->where(['id' => $id])->first();
        if ($media === null) {
            throw new NotFoundException(sprintf('Media %d not found.', $id));
        }

        $usedBy = $this->fetchTable('PageSections')->find()
            ->where(['media_id' => $id])
            ->count();
        if ($usedBy > 0) {
            $this->Flash->error(sprintf('Cannot delete: %d section(s) currently use this media.', $usedBy));

            return $this->redirect(['action' => 'index']);
        }

        $mediaTable->delete($media);

        $relativePath = (string)$media->get('file_url');
        if ($relativePath !== '') {
            $absolute = WWW_ROOT . ltrim($relativePath, '/');
            if (is_file($absolute)) {
                unlink($absolute);
            }
        }

        $this->Flash->success('Media deleted.');

        return $this->redirect(['action' => 'index']);
    }
}

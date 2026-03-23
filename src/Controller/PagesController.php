<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/5/en/controllers/pages-controller.html
 */
class PagesController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['home', 'contact', 'display']);
    }

    public function home(): ?Response
    {
        return null;
    }

    public function contact(): ?Response
    {
        $messagesTable = $this->fetchTable('Messages');
        $session = $this->request->getSession();
        $sourcePage = $this->resolveSourcePage();
        $enquirySubjects = [
            'Pottery lesson booking' => 'Pottery lesson booking',
            'Knitting lesson booking' => 'Knitting lesson booking',
            'Trial lesson booking' => 'Trial lesson booking',
            'General enquiry' => 'General enquiry',
        ];
        $enquiry = $messagesTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $enquiryData = [
                'sender_name' => trim((string)$this->request->getData('sender_name')),
                'sender_email' => trim((string)$this->request->getData('sender_email')),
                'sender_phone' => trim((string)$this->request->getData('sender_phone')),
                'source_page' => $sourcePage,
                'subject' => trim((string)$this->request->getData('subject')),
                'message_text' => trim((string)$this->request->getData('message_text')),
                'message_type' => 'contact_form',
                'message_status' => 'unread',
                'sent_at' => DateTime::now(),
            ];
            $enquiry = $messagesTable->newEntity($enquiryData, ['validate' => 'contactForm']);

            $honeypot = trim((string)$this->request->getData('website'));
            if ($honeypot !== '') {
                $this->Flash->success(__('Thank you. Your enquiry has been received.'));

                return $this->redirect([
                    'action' => 'contact',
                    '?' => ['from' => $sourcePage],
                    '#' => 'enquiry',
                ]);
            }

            $expectedCaptcha = (string)$session->read('Enquiry.captchaAnswer');
            $submittedCaptcha = trim((string)$this->request->getData('captcha_answer'));
            if ($expectedCaptcha === '' || $submittedCaptcha !== $expectedCaptcha) {
                $enquiry->setError('captcha_answer', ['Please solve the CAPTCHA question correctly.']);
            }

            if (!$enquiry->getErrors() && $messagesTable->save($enquiry)) {
                $session->delete('Enquiry');
                $this->Flash->success(__('Thanks, your enquiry has been sent. Our team will be in touch soon.'));

                return $this->redirect([
                    'action' => 'contact',
                    '?' => ['from' => $sourcePage],
                    '#' => 'enquiry',
                ]);
            }

            $this->Flash->error(__('Please review the form and try again.'));
        }

        $challenge = $this->buildCaptchaChallenge();
        $session->write('Enquiry.captchaAnswer', (string)$challenge['answer']);

        $this->set(compact('enquiry', 'enquirySubjects', 'sourcePage'));
        $this->set('captchaQuestion', $challenge['question']);

        return null;
    }

    private function resolveSourcePage(): string
    {
        $submittedSource = trim((string)$this->request->getData('source_page'));
        if ($submittedSource !== '') {
            return $this->normaliseSourcePage($submittedSource);
        }

        $querySource = trim((string)$this->request->getQuery('from'));
        if ($querySource !== '') {
            return $this->normaliseSourcePage($querySource);
        }

        $referer = trim($this->request->getHeaderLine('Referer'));
        if ($referer !== '') {
            $path = (string)parse_url($referer, PHP_URL_PATH);
            $path = trim($path, '/');
            $base = trim((string)$this->request->getAttribute('base'), '/');

            if ($base !== '' && str_starts_with($path, $base)) {
                $path = trim(substr($path, strlen($base)), '/');
            }

            if ($path === '') {
                return 'homepage';
            }

            return $this->normaliseSourcePage($path);
        }

        return 'contact-page';
    }

    private function normaliseSourcePage(string $sourcePage): string
    {
        $normalised = strtolower(trim($sourcePage));
        $normalised = str_replace('\\', '/', $normalised);
        $normalised = preg_replace('/[^a-z0-9\/\-_]+/', '-', $normalised) ?? '';
        $normalised = trim($normalised, '-/');

        if ($normalised === '' || $normalised === 'home' || $normalised === 'index') {
            return 'homepage';
        }

        if ($normalised === 'contact') {
            return 'contact-page';
        }

        return substr(str_replace('/', '-', $normalised), 0, 255);
    }

    private function buildCaptchaChallenge(): array
    {
        $left = random_int(2, 9);
        $right = random_int(1, 8);

        return [
            'question' => sprintf('%d + %d = ?', $left, $right),
            'answer' => $left + $right,
        ];
    }

    /**
     * Displays a view
     *
     * @param string ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\ForbiddenException When a directory traversal attempt.
     * @throws \Cake\View\Exception\MissingTemplateException When the view file could not
     *   be found and in debug mode.
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not
     *   be found and not in debug mode.
     * @throws \Cake\View\Exception\MissingTemplateException In debug mode.
     */
    public function display(string ...$path): ?Response
    {
        if (!$path) {
            return $this->redirect('/');
        }
        if (in_array('..', $path, true) || in_array('.', $path, true)) {
            throw new ForbiddenException();
        }
        $page = $subpage = null;

        if (!empty($path[0])) {
            $page = $path[0];
        }
        if (!empty($path[1])) {
            $subpage = $path[1];
        }
        $this->set(compact('page', 'subpage'));

        try {
            return $this->render(implode('/', $path));
        } catch (MissingTemplateException $exception) {
            if (Configure::read('debug')) {
                throw $exception;
            }
            throw new NotFoundException();
        }
    }
}

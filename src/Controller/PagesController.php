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
use Cake\Http\Client;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\View\Exception\MissingTemplateException;
use Throwable;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/5/en/controllers/pages-controller.html
 */
class PagesController extends AppController
{
    private const RECAPTCHA_TEST_SITE_KEY = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
    private const RECAPTCHA_TEST_SECRET_KEY = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

    /**
     * Before filter.
     *
     * @param mixed $event Event.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['home', 'contact', 'requestAccess', 'display']);
    }

    /**
     * Home.
     */
    public function home(): ?Response
    {
        return null;
    }

    /**
     * Contact.
     */
    public function contact(): ?Response
    {
        $messagesTable = $this->fetchTable('Messages');
        $session = $this->request->getSession();
        $sourcePage = $this->resolveSourcePage();
        $recaptcha = $this->buildRecaptchaViewState();
        $enquirySubjects = [
        'general' => 'General',
        'book-a-class' => 'Book a Class',
        'feedback' => 'Feedback/Suggestion',
        ];
        $enquiry = $messagesTable->newEmptyEntity();
        $requestAccount = false;

        if ($this->request->is('post')) {
            $requestAccount = !empty($this->request->getData('request_account'));
            $messageText = trim((string)$this->request->getData('message_text'));
            $studentName = trim((string)$this->request->getData('student_name'));
            $studentDob = trim((string)$this->request->getData('student_dob'));
            $classType = trim((string)$this->request->getData('class_type'));

            if ($studentName !== '' || $studentDob !== '') {
                $studentInfo = '';
                if ($studentName !== '') {
                    $studentInfo .= '[STUDENT_NAME: ' . $studentName . "]\n";
                }
                if ($studentDob !== '') {
                    $studentInfo .= '[STUDENT_DOB: ' . $studentDob . "]\n";
                }
                if ($classType !== '') {
                    $studentInfo .= '[CLASS_TYPE: ' . $classType . "]\n";
                }
                $messageText = $studentInfo . "\n" . $messageText;
            }
            $subject = trim((string)$this->request->getData('subject'));
            if ($subject === 'book-a-class' && $classType !== '') {
                $subject = 'Book a Class - ' . ucfirst($classType);
            }
            $messageSourcePage = $sourcePage;
            $submittedAt = DateTime::now('Australia/Melbourne');
            $declaredAge = $this->normaliseDeclaredAge($this->request->getData('declared_age'));
            $selfDeclaredAdult = $requestAccount && !empty($this->request->getData('self_declared_adult'));

            if ($requestAccount) {
                $messageText = $this->buildAccountRequestMessageText(
                    $declaredAge,
                    $selfDeclaredAdult,
                    $messageText,
                );
                $subject = 'Customer portal request';
                $messageSourcePage = 'account-request';
            }

            $enquiryData = [
                'sender_name' => trim((string)$this->request->getData('sender_name')),
                'sender_email' => trim((string)$this->request->getData('sender_email')),
                'sender_phone' => trim((string)$this->request->getData('sender_phone')),
                'source_page' => $messageSourcePage,
                'subject' => $subject,
                'message_text' => $messageText,
                'message_type' => 'contact_form',
                'message_status' => 'unread',
                'sent_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ];
            $enquiry = $messagesTable->newEntity($enquiryData, ['validate' => 'contactForm']);

            if ($requestAccount && $declaredAge === null) {
                $enquiry->setError('declared_age', ['Please tell us your current age.']);
            }

            if ($requestAccount && $selfDeclaredAdult && $declaredAge !== null && $declaredAge < 18) {
                $enquiry->setError('self_declared_adult', ['Your age and 18+ declaration do not match.']);
            }

            if ($requestAccount && !$selfDeclaredAdult && $declaredAge !== null && $declaredAge >= 18) {
                $enquiry->setError('self_declared_adult', ['Please confirm whether you are 18 or older.']);
            }

            if ($studentDob !== '' && strtotime($studentDob) > strtotime(date('Y-m-d'))) {
                $enquiry->setError('student_dob', ['Student date of birth cannot be in the future.']);
            }

            $honeypot = trim((string)$this->request->getData('website'));
            if ($honeypot !== '') {
                $this->Flash->success(
                    $requestAccount
                        ? __('Thanks. Your account request has been sent to our admin team.')
                        : __('Thank you. Your enquiry has been received.'),
                    ['key' => 'enquiry'],
                );

                return $this->redirect([
                    'action' => 'contact',
                    '?' => ['from' => $sourcePage],
                    '#' => 'enquiry',
                ]);
            }

            if (!$this->verifyRecaptcha((string)$this->request->getData('g-recaptcha-response'))) {
                $enquiry->setError('g-recaptcha-response', ['Please complete the CAPTCHA.']);
            }

            if (!$enquiry->getErrors() && $messagesTable->save($enquiry)) {
                $session->delete('Enquiry');
                $this->Flash->success(
                    $requestAccount
                        ? __('Thanks. Your account request has been sent to our admin team.')
                        : __('Thank you. Your enquiry has been received.'),
                    ['key' => 'enquiry'],
                );

                return $this->redirect([
                    'action' => 'contact',
                    '?' => ['from' => $sourcePage],
                    '#' => 'enquiry',
                ]);
            }
        }

        $this->set(compact('enquiry', 'enquirySubjects', 'sourcePage', 'requestAccount', 'recaptcha'));

        return null;
    }

    /**
     * Request access.
     */
    public function requestAccess(): ?Response
    {
        return $this->redirect([
            'action' => 'contact',
            '#' => 'enquiry',
        ]);
    }

    /**
     * Resolve source page.
     */
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

    /**
     * Normalise source page.
     *
     * @param mixed $sourcePage Sourcepage.
     */
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

    /**
     * Verify recaptcha.
     *
     * @param mixed $recaptchaResponse Recaptcharesponse.
     */
    private function verifyRecaptcha(string $recaptchaResponse): bool
    {
        if (!$this->isRecaptchaChallengeEnabled()) {
            return true;
        }

        if ($recaptchaResponse === '') {
            return false;
        }

        $secretKey = $this->getRecaptchaSecretKey();

        try {
            $client = new Client(['timeout' => 3]);
            $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $recaptchaResponse,
                'remoteip' => $this->request->clientIp(),
            ]);
        } catch (Throwable $exception) {
            return false;
        }

        if (!$response->isOk()) {
            return false;
        }

        $captchaSuccess = $response->getJson();

        return (bool)($captchaSuccess['success'] ?? false);
    }

    /**
     * @return array{mode: string, siteKey: string, helpText: string, footerNote: string}
     */
    private function buildRecaptchaViewState(): array
    {
        if ($this->isRecaptchaChallengeEnabled()) {
            return [
                'mode' => 'live',
                'siteKey' => $this->getRecaptchaSiteKey(),
                'helpText' => 'Complete the CAPTCHA challenge before submitting your enquiry.',
                'footerNote' => 'Protected by CAPTCHA and anti-spam checks.',
            ];
        }

        if ($this->isRecaptchaTestMode()) {
            return [
                'mode' => 'test',
                'siteKey' => '',
                'helpText' => 'CAPTCHA test mode is active in this environment, so you can submit without completing a live challenge.',
                'footerNote' => 'Protected by anti-spam checks while CAPTCHA runs in test mode.',
            ];
        }

        return [
            'mode' => 'disabled',
            'siteKey' => '',
            'helpText' => 'CAPTCHA is unavailable in this environment right now. You can still submit the form while basic anti-spam checks remain enabled.',
            'footerNote' => 'Protected by anti-spam checks while CAPTCHA is unavailable.',
        ];
    }

    /**
     * Is recaptcha challenge enabled.
     */
    private function isRecaptchaChallengeEnabled(): bool
    {
        return $this->getRecaptchaSiteKey() !== ''
            && $this->getRecaptchaSecretKey() !== ''
            && !$this->isRecaptchaTestMode();
    }

    /**
     * Is recaptcha test mode.
     */
    private function isRecaptchaTestMode(): bool
    {
        $siteKey = $this->getRecaptchaSiteKey();
        $secretKey = $this->getRecaptchaSecretKey();

        return ($siteKey !== '' && hash_equals(self::RECAPTCHA_TEST_SITE_KEY, $siteKey))
            || ($secretKey !== '' && hash_equals(self::RECAPTCHA_TEST_SECRET_KEY, $secretKey));
    }

    /**
     * Get recaptcha site key.
     */
    private function getRecaptchaSiteKey(): string
    {
        return trim((string)Configure::read('Recaptcha.site_key'));
    }

    /**
     * Get recaptcha secret key.
     */
    private function getRecaptchaSecretKey(): string
    {
        return trim((string)Configure::read('Recaptcha.secret_key'));
    }

    /**
     * Normalise declared age.
     *
     * @param mixed $declaredAge Declaredage.
     */
    private function normaliseDeclaredAge(mixed $declaredAge): ?int
    {
        if ($declaredAge === null || $declaredAge === '') {
            return null;
        }

        if (is_numeric($declaredAge)) {
            $age = (int)$declaredAge;

            if ($age >= 1 && $age <= 120) {
                return $age;
            }
        }

        return null;
    }

    /**
     * Build account request message text.
     *
     * @param mixed $declaredAge Declaredage.
     * @param mixed $selfDeclaredAdult Selfdeclaredadult.
     * @param mixed $messageText Messagetext.
     */
    private function buildAccountRequestMessageText(?int $declaredAge, bool $selfDeclaredAdult, string $messageText): string
    {
        $parts = [
            '[REQUEST TYPE: customer_access]',
            '[REQUESTED PORTAL: customer]',
            '[LEGACY PROFILE TYPE: student]',
            '[DECLARED AGE: ' . ($declaredAge ?? 'unknown') . ']',
            '[SELF DECLARED 18+: ' . ($selfDeclaredAdult ? 'yes' : 'no') . ']',
        ];

        if ($messageText !== '') {
            $parts[] = '';
            $parts[] = $messageText;
        }

        return implode("\n", $parts);
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

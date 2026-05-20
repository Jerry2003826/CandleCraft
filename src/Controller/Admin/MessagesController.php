<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Mailer\EnquiryReplyMailer;
use App\Mailer\PortalAccountMailer;
use Cake\I18n\DateTime;
use Cake\Routing\Router;
use RuntimeException;
use Throwable;

class MessagesController extends AppController
{
    private const DEFAULT_PORTAL_PASSWORD = 'FIT3047185';

    /**
     * Prefix a raw application-relative path (e.g. "/admin/messages") with the
     * current request's webroot, so it works in subdirectory deployments such
     * as production at https://host/production/. Returns the path unchanged
     * for absolute URLs (with a scheme) and for empty values.
     */
    private function withWebrootPrefix(string $path): string
    {
        if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $webroot = (string)($this->request->getAttribute('webroot') ?? '/');
        $prefix = rtrim($webroot, '/');
        $normalized = '/' . ltrim($path, '/');

        // Avoid double-prefixing if the caller already included the webroot.
        if ($prefix !== '' && str_starts_with($normalized, $prefix . '/')) {
            return $normalized;
        }

        return $prefix . $normalized;
    }

    /**
     * Index.
     */
    public function index(): void
    {
        $messagesTable = $this->fetchTable('Messages');
        $query = $messagesTable->find()
            ->contain(['SenderUsers', 'ReceiverUsers'])
            ->where(['Messages.message_type' => 'contact_form'])
            ->orderBy(['Messages.sent_at' => 'DESC']);

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['unread', 'read', 'replied', 'archived'])) {
            $query->where(['Messages.message_status' => $status]);
        }

        $messages = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('messages', 'status'));
    }

    /**
     * View.
     *
     * @param mixed $id Id.
     */
    public function view(?string $id = null): void
    {
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->get($id, contain: ['SenderUsers', 'ReceiverUsers']);
        $requestMeta = $this->extractAccountRequestMeta($message);
        $existingUser = null;
        $linkedStudent = null;

        if (!empty($message->sender_email)) {
            $existingUser = $this->fetchTable('Users')->find()
                ->where(['Users.email' => $message->sender_email])
                ->first();

            if ($existingUser && in_array((string)$existingUser->user_role, ['student', 'customer'], true)) {
                $linkedStudent = $this->fetchTable('Students')->find()
                    ->where(['Students.user_id' => $existingUser->user_id])
                    ->first();
            }
        }

        if ($message->message_status === 'unread' && !$this->request->getQuery('keep_unread')) {
            $message->message_status = 'read';
            $messagesTable->save($message);
        }

        $replies = $messagesTable->find()
            ->where(['Messages.parent_message_id' => $message->message_id])
            ->contain(['SenderUsers', 'ReceiverUsers'])
            ->orderBy(['Messages.sent_at' => 'ASC'])
            ->all();

        $this->set(compact('message', 'replies', 'requestMeta', 'existingUser', 'linkedStudent'));
    }

    /**
     * Create account.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function createAccount(?string $id = null)
    {
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->get($id);
        $requestMeta = $this->extractAccountRequestMeta($message);

        if (!$requestMeta['is_account_request']) {
            $this->Flash->error(__(
                'This message is not an account request.',
            ));

            return $this->redirect(['action' => 'view', $id]);
        }

        $accountStatusOptions = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
        ];
        $profileStatusOptions = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];

        $account = [
            'user_role' => 'student',
            'username' => $this->buildSuggestedUsername(
                (string)$message->sender_email,
                (string)$message->sender_name,
            ),
            'email' => (string)$message->sender_email,
            'password' => self::DEFAULT_PORTAL_PASSWORD,
            'account_status' => 'active',
            'profile_name' => (string)$message->sender_name,
            'phone_number' => (string)$message->sender_phone,
            'student_status' => 'active',
            'teacher_status' => 'active',
            'declared_age' => $requestMeta['declared_age'],
            'self_declared_adult' => (bool)$requestMeta['self_declared_adult'],
            'date_of_birth' => '',
            'medical_notes' => '',
            'specialization' => '',
            'hire_date' => '',
        ];

        $existingUser = null;
        $linkedStudent = null;
        if (!empty($message->sender_email)) {
            $existingUser = $this->fetchTable('Users')->find()
                ->where(['Users.email' => $message->sender_email])
                ->first();

            if ($existingUser && in_array((string)$existingUser->user_role, ['student', 'customer'], true)) {
                $linkedStudent = $this->fetchTable('Students')->find()
                    ->where(['Students.user_id' => $existingUser->user_id])
                    ->first();
            }
        }

        if ($this->request->is('post')) {
            $account = array_merge($account, $this->request->getData());
            // Customer access requests are still provisioned through the legacy student profile flow.
            $account['user_role'] = 'student';
            $account['username'] = trim((string)($account['username'] ?? ''));
            $account['email'] = trim((string)($account['email'] ?? ''));
            $account['profile_name'] = trim((string)($account['profile_name'] ?? ''));
            $account['phone_number'] = trim((string)($account['phone_number'] ?? ''));
            $account['medical_notes'] = trim((string)($account['medical_notes'] ?? ''));
            $account['specialization'] = trim((string)($account['specialization'] ?? ''));
            $account['declared_age'] = $this->normaliseDeclaredAge($account['declared_age'] ?? null);
            $account['password'] = self::DEFAULT_PORTAL_PASSWORD;

            if ($existingUser) {
                $this->Flash->error(__(
                    'An account already exists for {0}.',
                    $existingUser->email,
                ));
            } else {
                try {
                    $createdAccount = $this->createRequestedAccount($account);
                    $emailSent = false;
                    $mailFailureMessage = null;

                    try {
                        $this->sendPortalCredentialsEmail(
                            recipientName: (string)$account['profile_name'],
                            recipientEmail: (string)$account['email'],
                            loginEmail: (string)$account['email'],
                            temporaryPassword: self::DEFAULT_PORTAL_PASSWORD,
                            changePasswordUrl: Router::url([
                                'prefix' => false,
                                'controller' => 'Users',
                                'action' => 'resetPassword',
                                $createdAccount['password_reset_token'],
                            ], true),
                            declaredAge: $account['declared_age'],
                        );
                        $emailSent = true;
                    } catch (Throwable $mailException) {
                        $mailFailureMessage = $mailException->getMessage();
                    }

                    $message->message_status = 'replied';
                    $messagesTable->save($message);

                    if ($emailSent) {
                        $this->Flash->success(__(
                            'Account created for {0} and the login email has been sent.',
                            $account['email'],
                        ));
                    } else {
                        $this->Flash->warning(__(
                            'Account created for {0}, but the email could not be sent automatically. Temporary password: {1}. {2}',
                            $account['email'],
                            self::DEFAULT_PORTAL_PASSWORD,
                            $mailFailureMessage ?: 'Please share the credentials manually or configure email delivery.',
                        ));
                    }

                    if ($createdAccount['role'] === 'student' && isset($createdAccount['profile']->student_id)) {
                        return $this->redirect([
                            'prefix' => 'Admin',
                            'controller' => 'Students',
                            'action' => 'view',
                            $createdAccount['profile']->student_id,
                            '?' => ['message' => $message->message_id],
                        ]);
                    }

                    return $this->redirect(['action' => 'view', $message->message_id]);
                } catch (RuntimeException $exception) {
                    $this->Flash->error($exception->getMessage());
                }
            }
        }

        $this->set(compact(
            'message',
            'requestMeta',
            'existingUser',
            'linkedStudent',
            'account',
            'accountStatusOptions',
            'profileStatusOptions',
        ));
        $this->set('defaultPortalPassword', self::DEFAULT_PORTAL_PASSWORD);
    }

    /**
     * Reply.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function reply(?string $id = null)
    {
        $messagesTable = $this->fetchTable('Messages');
        $originalMessage = $messagesTable->get($id, contain: ['SenderUsers']);
        $replySubject = $this->buildReplySubject((string)$originalMessage->subject);

        if ($originalMessage->message_status === 'unread') {
            $originalMessage->message_status = 'read';
            $messagesTable->save($originalMessage);
        }

        if ($this->request->is('post')) {
            $identity = $this->Authentication->getIdentity();
            $recipientEmail = trim((string)($originalMessage->sender_email ?? ''));
            $recipientName = trim((string)($originalMessage->sender_name ?? ''));

            if ($recipientEmail === '') {
                $this->Flash->error(__(
                    'This enquiry does not include an external email address.',
                ));

                return $this->redirect(['action' => 'view', $id]);
            }

            $replyData = [
                'sender_user_id' => $identity->get('user_id'),
                'receiver_user_id' => $originalMessage->sender_user_id,
                'parent_message_id' => $originalMessage->message_id,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'subject' => $replySubject,
                'message_text' => trim((string)$this->request->getData('message_text')),
                'message_type' => 'email_reply',
                'message_status' => 'read',
                'delivery_status' => 'pending',
                'sent_at' => DateTime::now(),
            ];

            $reply = $messagesTable->newEntity($replyData);
            if ($reply->hasErrors()) {
                $this->Flash->error($this->extractFirstValidationError($reply->getErrors(), 'Failed to prepare the enquiry reply.'));
            } elseif ($messagesTable->save($reply)) {
                try {
                    $this->sendEnquiryReplyEmail(
                        recipientName: $recipientName,
                        recipientEmail: $recipientEmail,
                        subject: (string)$reply->subject,
                        replyMessage: (string)$reply->message_text,
                        originalSubject: (string)$originalMessage->subject,
                        originalMessage: (string)$originalMessage->message_text,
                        sentByName: (string)($identity->get('username') ?? 'CandleCraft Academy'),
                    );

                    $reply->delivery_status = 'sent';
                    $messagesTable->save($reply);

                    $originalMessage->message_status = 'replied';
                    $messagesTable->save($originalMessage);

                    $this->Flash->success(__(
                        'Reply sent successfully to {0}.',
                        $recipientEmail,
                    ));

                    $postedReturnUrl = (string)($this->request->getData('return_url') ?? '');

                    return $this->redirect(
                        $postedReturnUrl !== ''
                            ? $this->withWebrootPrefix($postedReturnUrl)
                            : ['action' => 'view', $id],
                    );
                } catch (Throwable $mailException) {
                    $reply->delivery_status = 'failed';
                    $messagesTable->save($reply);

                    if ($originalMessage->message_status !== 'read') {
                        $originalMessage->message_status = 'read';
                        $messagesTable->save($originalMessage);
                    }

                    $this->Flash->warning(__(
                        'The enquiry reply was saved, but the email could not be delivered automatically. {0}',
                        $mailException->getMessage(),
                    ));
                }
            } else {
                $this->Flash->error(__(
                    'Failed to save the enquiry reply. Please try again.',
                ));
            }
        }

        $this->set(compact('originalMessage', 'replySubject'));
    }

    /**
     * Mark unread.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function markUnread(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->get($id);

        if ($message->message_status === 'archived') {
            $this->Flash->warning(__(
                'Archived enquiries cannot be marked as unread.',
            ));

            return $this->redirect($this->referer(['action' => 'view', $id], true));
        }

        $message->message_status = 'unread';
        if ($messagesTable->save($message)) {
            $this->Flash->success(__(
                'The enquiry has been marked as unread.',
            ));
        } else {
            $this->Flash->error(__(
                'The enquiry could not be marked as unread. Please try again.',
            ));
        }

        $returnUrl = (string)($this->request->getQuery('return_url') ?? '');
        if ($returnUrl !== '' && $this->request->getQuery('redirect') === 'return_url') {
            return $this->redirect($this->withWebrootPrefix($returnUrl));
        }

        $query = ['keep_unread' => 1];
        if ($returnUrl !== '') {
            $query['return_url'] = $returnUrl;
        }

        return $this->redirect(['action' => 'view', $id, '?' => $query]);
    }

    /**
     * Delete.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->get($id);

        if ($message->message_status !== 'archived') {
            $this->Flash->warning(__(
                'Please archive the enquiry before deleting it permanently.',
            ));

            return $this->redirect($this->referer(['action' => 'view', $id], true));
        }

        if ($messagesTable->delete($message)) {
            $this->Flash->success(__(
                'The enquiry has been deleted.',
            ));
        } else {
            $this->Flash->error(__(
                'The enquiry could not be deleted. Please try again.',
            ));
        }

        return $this->redirect($this->referer(['action' => 'index', '?' => ['status' => 'archived']], true));
    }

    /**
     * Archive.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function archive(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->get($id);

        if ($message->message_status === 'archived') {
            $this->Flash->info(__(
                'This enquiry is already archived.',
            ));

            return $this->redirect($this->referer(['action' => 'index', '?' => ['status' => 'archived']], true));
        }

        $message->message_status = 'archived';
        if ($messagesTable->save($message)) {
            $this->Flash->success(__(
                'The enquiry has been archived.',
            ));
        } else {
            $this->Flash->error(__(
                'The enquiry could not be archived. Please try again.',
            ));
        }

        return $this->redirect($this->referer(['action' => 'index'], true));
    }

    /**
     * Restore.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function restore(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->get($id);

        if ($message->message_status !== 'archived') {
            $this->Flash->info(__(
                'Only archived enquiries can be restored.',
            ));

            return $this->redirect($this->referer(['action' => 'index'], true));
        }

        $message->message_status = 'read';
        if ($messagesTable->save($message)) {
            $this->Flash->success(__(
                'The enquiry has been restored to the inbox.',
            ));
        } else {
            $this->Flash->error(__(
                'The enquiry could not be restored. Please try again.',
            ));
        }

        return $this->redirect($this->referer(['action' => 'index'], true));
    }

    /**
     * Extract account request meta.
     *
     * @param mixed $message Message.
     */
    private function extractAccountRequestMeta(object $message): array
    {
        $messageText = (string)($message->message_text ?? '');
        $declaredAge = $this->normaliseDeclaredAge($this->extractTaggedValue($messageText, 'DECLARED AGE'));
        $legacyDeclaredAdult = strtolower($this->extractTaggedValue($messageText, 'SELF DECLARED 18+') ?? 'no') === 'yes';

        if ($declaredAge === null && $legacyDeclaredAdult) {
            $declaredAge = 18;
        }

        $cleanMessageText = preg_replace(
            [
                '/^\[REQUEST TYPE:\s*[^\]]+\]\s*$/mi',
                '/^\[REQUESTED ROLE:\s*[^\]]+\]\s*$/mi',
                '/^\[REQUESTED PORTAL:\s*[^\]]+\]\s*$/mi',
                '/^\[LEGACY PROFILE TYPE:\s*[^\]]+\]\s*$/mi',
                '/^\[SELF DECLARED 18\+:\s*[^\]]+\]\s*$/mi',
                '/^\[DECLARED AGE:\s*[^\]]+\]\s*$/mi',
                '/^\[STUDENT_NAME:\s*[^\]]+\]\s*$/mi',
                '/^\[STUDENT_DOB:\s*[^\]]+\]\s*$/mi',
                '/^\[CLASS_TYPE:\s*[^\]]+\]\s*$/mi',
            ],
            '',
            $messageText,
        );

        $legacyProfileType = $this->extractTaggedValue($messageText, 'LEGACY PROFILE TYPE') ?? 'student';
        $requestedPortal = $this->extractTaggedValue($messageText, 'REQUESTED PORTAL');

        return [
            'is_account_request' => (($message->source_page ?? null) === 'account-request')
                || str_contains($messageText, '[REQUEST TYPE: account_access]')
                || str_contains($messageText, '[REQUEST TYPE: customer_access]'),
            'requested_role' => 'customer',
            'requested_role_label' => 'Customer',
            'requested_portal' => $requestedPortal ?: 'customer',
            'legacy_profile_type' => $legacyProfileType,
            'legacy_profile_label' => ucfirst($legacyProfileType),
            'declared_age' => $declaredAge,
            'self_declared_adult' => $legacyDeclaredAdult,
            'clean_message_text' => trim((string)$cleanMessageText),
        ];
    }

    /**
     * Extract tagged value.
     *
     * @param mixed $messageText Messagetext.
     * @param mixed $label Label.
     */
    private function extractTaggedValue(string $messageText, string $label): ?string
    {
        $pattern = '/^\[' . preg_quote($label, '/') . ':\s*([^\]]+)\]\s*$/mi';
        if (preg_match($pattern, $messageText, $matches) === 1) {
            return strtolower(trim($matches[1]));
        }

        return null;
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
     * Build suggested username.
     *
     * @param mixed $email Email.
     * @param mixed $name Name.
     */
    private function buildSuggestedUsername(string $email, string $name): string
    {
        $usersTable = $this->fetchTable('Users');

        $base = '';
        if ($email !== '' && str_contains($email, '@')) {
            $base = strstr($email, '@', true) ?: '';
        }

        if ($base === '') {
            $base = strtolower(str_replace(' ', '.', trim($name)));
        }

        $base = preg_replace('/[^a-z0-9_.-]+/i', '.', strtolower($base)) ?? '';
        $base = trim($base, '.-_');
        if ($base === '') {
            $base = 'user';
        }
        if (strlen($base) < 3) {
            $base = str_pad($base, 3, 'x');
        }

        $candidate = substr($base, 0, 50);
        $counter = 1;
        while ($usersTable->find()->where(['Users.username' => $candidate])->count() > 0) {
            $suffix = '.' . $counter;
            $candidate = substr($base, 0, max(1, 50 - strlen($suffix))) . $suffix;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Create requested account.
     *
     * @param mixed $account Account.
     */
    private function createRequestedAccount(array $account): array
    {
        $role = (string)($account['user_role'] ?? '');
        if (!in_array($role, ['student', 'teacher'], true)) {
            throw new RuntimeException(
                __('Please choose a valid account type.'),
            );
        }

        $usersTable = $this->fetchTable('Users');
        $connection = $usersTable->getConnection();
        $passwordResetToken = bin2hex(random_bytes(32));
        $connection->begin();

        try {
            $user = $usersTable->newEntity(
                [
                    'username' => $account['username'] ?? '',
                    'email' => $account['email'] ?? '',
                    'password_hash' => $account['password'] ?? '',
                    'user_role' => $role,
                    'account_status' => $account['account_status'] ?? 'active',
                    'age_verified_by_admin' => false,
                    'self_declared_adult' => (bool)($account['self_declared_adult'] ?? false),
                    'reset_token' => $passwordResetToken,
                    'reset_token_expires' => new DateTime('+7 days'),
                ],
                [
                    'accessibleFields' => [
                        'user_role' => true,
                        'account_status' => true,
                        'age_verified_by_admin' => true,
                        'self_declared_adult' => true,
                        'reset_token' => true,
                        'reset_token_expires' => true,
                    ],
                ],
            );

            if ($user->hasErrors()) {
                throw new RuntimeException($this->extractFirstValidationError(
                    $user->getErrors(),
                    'The user account details are invalid.',
                ));
            }

            $savedUser = $usersTable->save($user);
            if (!$savedUser) {
                throw new RuntimeException($this->extractFirstValidationError(
                    $user->getErrors(),
                    'The user account could not be saved.',
                ));
            }

            if ($role === 'student') {
                $profileTable = $this->fetchTable('Students');
                $profile = $profileTable->newEntity([
                    'user_id' => $savedUser->user_id,
                    'student_name' => $account['profile_name'] ?? '',
                    'declared_age' => $account['declared_age'] ?? null,
                    'date_of_birth' => $account['date_of_birth'] ?: null,
                    'student_status' => $account['student_status'] ?? 'active',
                    'medical_notes' => $account['medical_notes'] ?? '',
                ]);
            } elseif ($role === 'teacher') {
                $profileTable = $this->fetchTable('Teachers');
                $profile = $profileTable->newEntity([
                    'user_id' => $savedUser->user_id,
                    'teacher_name' => $account['profile_name'] ?? '',
                    'phone_number' => $account['phone_number'] ?? '',
                    'specialization' => $account['specialization'] ?: null,
                    'teacher_status' => $account['teacher_status'] ?? 'active',
                    'hire_date' => $account['hire_date'] ?: null,
                ]);
            }

            if ($profile->hasErrors()) {
                throw new RuntimeException($this->extractFirstValidationError(
                    $profile->getErrors(),
                    'The profile details are invalid.',
                ));
            }

            if (!$profileTable->save($profile)) {
                throw new RuntimeException($this->extractFirstValidationError(
                    $profile->getErrors(),
                    'The profile could not be saved.',
                ));
            }

            $connection->commit();

            return [
                'role' => $role,
                'user' => $savedUser,
                'profile' => $profile,
                'password_reset_token' => $passwordResetToken,
            ];
        } catch (Throwable $exception) {
            $connection->rollback();

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException(
                'The account could not be created. Please try again.',
            );
        }
    }

    /**
     * Send portal credentials email.
     *
     * @param mixed $recipientName Recipientname.
     * @param mixed $recipientEmail Recipientemail.
     * @param mixed $loginEmail Loginemail.
     * @param mixed $temporaryPassword Temporarypassword.
     * @param mixed $changePasswordUrl Changepasswordurl.
     * @param mixed $declaredAge Declaredage.
     */
    private function sendPortalCredentialsEmail(
        string $recipientName,
        string $recipientEmail,
        string $loginEmail,
        string $temporaryPassword,
        string $changePasswordUrl,
        ?int $declaredAge,
    ): void {
        if ($recipientEmail === '') {
            throw new RuntimeException(
                'No requester email address is available for delivery.',
            );
        }

        $mailer = new PortalAccountMailer('default');
        $mailer->send('portalCredentials', [[
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'login_email' => $loginEmail,
            'temporary_password' => $temporaryPassword,
            'change_password_url' => $changePasswordUrl,
            'login_url' => Router::url([
                'prefix' => false,
                'controller' => 'Users',
                'action' => 'login',
            ], true),
            'declared_age' => $declaredAge,
        ]]);
    }

    /**
     * Send enquiry reply email.
     *
     * @param mixed $recipientName Recipientname.
     * @param mixed $recipientEmail Recipientemail.
     * @param mixed $subject Subject.
     * @param mixed $replyMessage Replymessage.
     * @param mixed $originalSubject Originalsubject.
     * @param mixed $originalMessage Originalmessage.
     * @param mixed $sentByName Sentbyname.
     */
    private function sendEnquiryReplyEmail(
        string $recipientName,
        string $recipientEmail,
        string $subject,
        string $replyMessage,
        string $originalSubject,
        string $originalMessage,
        string $sentByName,
    ): void {
        $mailer = new EnquiryReplyMailer('default');
        $mailer->send('enquiryReply', [[
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'reply_message' => $replyMessage,
            'original_subject' => $originalSubject,
            'original_message' => $originalMessage,
            'sent_by_name' => $sentByName,
        ]]);
    }

    /**
     * Build reply subject.
     *
     * @param mixed $subject Subject.
     */
    private function buildReplySubject(string $subject): string
    {
        $subject = trim($subject);
        if ($subject === '') {
            $subject = 'Enquiry';
        }

        return preg_match('/^re:/i', $subject) === 1 ? $subject : 'Re: ' . $subject;
    }

    /**
     * Extract first validation error.
     *
     * @param mixed $errors Errors.
     * @param mixed $fallback Fallback.
     */
    private function extractFirstValidationError(array $errors, string $fallback): string
    {
        foreach ($errors as $fieldErrors) {
            if (!is_array($fieldErrors)) {
                continue;
            }

            foreach ($fieldErrors as $message) {
                if (is_string($message) && $message !== '') {
                    return $message;
                }
            }
        }

        return $fallback;
    }
}

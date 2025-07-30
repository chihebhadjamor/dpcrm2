<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Action;
use App\Form\ChangePasswordType;
use App\Form\TwoFactorAuthType;
use App\Service\ActionHistoryService;
use App\Service\AppSettingsService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use DateTimeImmutable;

class UserController extends AbstractWebController
{
    private AppSettingsService $appSettingsService;
    private EmailService $emailService;

    /**
     * Verifies a TOTP code against a given secret
     *
     * @param string $secret The Base32 encoded secret
     * @param string $code The 6-digit code to verify
     * @param int $window Time window in 30-second units (default: 1 unit before and after)
     * @return bool Whether the code is valid
     */
    private function verifyTotpCode(string $secret, string $code, int $window = 1): bool
    {
        // Clean up the code (remove spaces, etc.)
        $code = preg_replace('/\s+/', '', $code);

        // Validate code format (must be 6 digits)
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // Get current timestamp
        $now = new DateTimeImmutable();
        $timestamp = $now->getTimestamp();

        // Check codes in the time window
        for ($i = -$window; $i <= $window; $i++) {
            $checkTime = floor($timestamp / 30) + $i;
            $generatedCode = $this->generateTotpCode($secret, $checkTime);

            if (hash_equals($generatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates a TOTP code for a given secret and timestamp
     *
     * @param string $secret The Base32 encoded secret
     * @param int|null $timestamp The timestamp to use (null for current time)
     * @return string The 6-digit TOTP code
     */
    private function generateTotpCode(string $secret, ?int $timestamp = null): string
    {
        // Remove padding characters
        $secret = str_replace('=', '', $secret);

        // Convert Base32 secret to binary
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secretBinary = '';

        // Process 8 characters (40 bits) at a time
        for ($i = 0; $i < strlen($secret); $i += 8) {
            $chunk = substr($secret, $i, 8);
            $buffer = 0;
            $bitsLeft = 0;

            // Process each character in the chunk
            for ($j = 0; $j < strlen($chunk); $j++) {
                $buffer <<= 5;
                $buffer |= strpos($base32Chars, $chunk[$j]);
                $bitsLeft += 5;

                if ($bitsLeft >= 8) {
                    $bitsLeft -= 8;
                    $secretBinary .= chr(($buffer >> $bitsLeft) & 0xFF);
                }
            }
        }

        // Use current timestamp if none provided
        if ($timestamp === null) {
            $timestamp = floor(time() / 30);
        }

        // Create binary timestamp (big-endian)
        $timestampBinary = pack('N*', 0, $timestamp);

        // Generate HMAC-SHA1 hash
        $hash = hash_hmac('sha1', $timestampBinary, $secretBinary, true);

        // Get offset from last 4 bits of the hash
        $offset = ord($hash[19]) & 0x0F;

        // Get 4 bytes from the hash starting at offset
        $value = unpack('N', substr($hash, $offset, 4))[1];

        // Remove the most significant bit (RFC 4226)
        $value = $value & 0x7FFFFFFF;

        // Get 6 digits
        $modulo = pow(10, 6);
        $code = str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);

        return $code;
    }

    public function __construct(AppSettingsService $appSettingsService, EmailService $emailService)
    {
        $this->appSettingsService = $appSettingsService;
        $this->emailService = $emailService;
    }

    /**
     * Check if the current user has admin access
     * Throws AccessDeniedException if not
     */
    private function denyAccessUnlessAdmin(): void
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You do not have permission to access this page.');
        }
    }
    #[Route('/users/create-ajax', name: 'app_create_user_ajax', methods: ['POST'])]
    public function createUserAjax(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();
        // Validate CSRF token
        $token = $request->request->get('_token');
        if (!$token) {
            return new JsonResponse(['error' => 'CSRF token is missing'], 400);
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('create_user', $token))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        // Get form data
        $username = $request->request->get('username');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $name = $request->request->get('name');

        // Validate required fields
        if (!$username || !$email || !$password) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email format'], 400);
        }

        // Check if email is already in use
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email is already in use'], 400);
        }

        // Check if username is already in use
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Username is already in use'], 400);
        }

        // Create a new user
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);

        // Hash the password
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $password
        );
        $user->setPassword($hashedPassword);

        // Set default role
        $user->setRoles(['ROLE_USER']);

        // Save to database
        $entityManager->persist($user);
        $entityManager->flush();

        // Send welcome email
        $emailSent = true;
        try {
            $this->emailService->sendWelcomeEmail(
                $user->getEmail(),
                $user->getUsername()
            );
        } catch (\Exception $e) {
            // Log the error but don't prevent user creation
            error_log(sprintf(
                'Failed to send welcome email to user ID %d (%s): %s',
                $user->getId(),
                $user->getEmail(),
                $e->getMessage()
            ));
            $emailSent = false;
        }

        // Return the created user data with email status
        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'emailSent' => $emailSent,
            'message' => $emailSent
                ? sprintf('User "%s" created successfully. A welcome email has been sent.', $user->getUsername())
                : sprintf('Warning: User "%s" was created, but the welcome email could not be sent. Please check the system logs.', $user->getUsername())
        ]);
    }

    #[Route('/users', name: 'app_users')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Only allow administrators to access this page
        $this->denyAccessUnlessAdmin();

        $users = $entityManager->getRepository(User::class)->findBy([], ['id' => 'DESC']);

        // Return the view with users
        return $this->render('user/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/api/users', name: 'app_get_users', methods: ['GET'])]
    public function getUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        // Allow all authenticated users to access this endpoint
        // Only get active users (where disabled = false)
        $users = $entityManager->getRepository(User::class)->findBy(['disabled' => false]);

        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'name' => $user->getUsername() // Include name for compatibility with frontend
            ];
        }

        return new JsonResponse($usersData);
    }

    #[Route('/users/{userId}/actions', name: 'app_user_actions', methods: ['GET'])]
    public function getUserActions(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();
        try {
            $user = $entityManager->getRepository(User::class)->find($userId);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Use query builder to get actions where the user is the owner
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->select('a')
                ->from(Action::class, 'a')
                ->where('a.owner = :user')
                ->orderBy('a.nextStepDate', 'DESC')
                ->setParameter('user', $user);

            $actions = $queryBuilder->getQuery()->getResult();

            $actionsData = [];
            foreach ($actions as $action) {
                $actionsData[] = [
                    'id' => $action->getId(),
                    'title' => $action->getTitle(),
                    'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
                    'createdAt' => $this->appSettingsService->formatDateTime($action->getCreatedAt()),
                    'owner' => $action->getOwner()->getUsername()
                ];
            }

            return new JsonResponse($actionsData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while fetching actions: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/users/{userId}/open-actions', name: 'app_user_open_actions', methods: ['GET'])]
    public function getUserOpenActions(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();
        try {
            $user = $entityManager->getRepository(User::class)->find($userId);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Use query builder to get open actions where:
            // 1. The user is the owner AND
            // 2. The action is not closed (closed = false)
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->select('a')
                ->from(Action::class, 'a')
                ->where('a.owner = :user')
                ->andWhere('a.closed = :closed')
                ->orderBy('a.nextStepDate', 'ASC')
                ->setParameter('user', $user)
                ->setParameter('closed', false);

            $actions = $queryBuilder->getQuery()->getResult();

            $actionsData = [];
            foreach ($actions as $action) {
                $actionsData[] = [
                    'id' => $action->getId(),
                    'title' => $action->getTitle(),
                    'accountName' => $action->getAccount() ? $action->getAccount()->getName() : null,
                    'priority' => $action->getAccount() ? $action->getAccount()->getPriority() : null,
                    'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
                    'createdAt' => $this->appSettingsService->formatDateTime($action->getCreatedAt()),
                    'owner' => $action->getOwner()->getUsername()
                ];
            }

            return new JsonResponse($actionsData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while fetching open actions: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/my-actions', name: 'app_my_actions', methods: ['GET'])]
    /**
     * Get all actions related to the current user, both open and closed
     */
    public function getMyActions(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], 401);
            }

            // Get all actions where the current user is the owner
            // Sort by nextStepDate in ascending order (most urgent first)
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->select('a')
                ->from(Action::class, 'a')
                ->where('a.owner = :user')
                ->orderBy('a.nextStepDate', 'ASC')
                ->setParameter('user', $user);

            $actions = $queryBuilder->getQuery()->getResult();

            $actionsData = [];
            foreach ($actions as $action) {
                // Get the closure date from the History entity if the action is closed
                $dateClosed = null;
                if ($action->isClosed()) {
                    // Find the history entry that corresponds to when the action was closed
                    $histories = $action->getHistories();
                    foreach ($histories as $history) {
                        if (strpos($history->getNote(), 'closed') !== false) {
                            $dateClosed = $this->appSettingsService->formatDateTime($history->getCreatedAt());
                            break;
                        }
                    }

                    // If no history entry is found, fall back to the action's dateClosed field
                    if ($dateClosed === null && $action->getDateClosed()) {
                        $dateClosed = $this->appSettingsService->formatDateTime($action->getDateClosed());
                    }
                }

                $actionsData[] = [
                    'id' => $action->getId(),
                    'title' => $action->getTitle(),
                    'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
                    'createdAt' => $this->appSettingsService->formatDateTime($action->getCreatedAt()),
                    'owner' => $action->getOwner()->getUsername(),
                    'account' => $action->getAccount() ? $action->getAccount()->getName() : null,
                    'closed' => $action->isClosed(),
                    'dateClosed' => $dateClosed
                ];
            }

            return new JsonResponse($actionsData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while fetching actions: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/my-backlog', name: 'app_my_backlog', methods: ['GET'])]
    /**
     * Display the current user's backlog (personal actions page)
     */
    public function myBacklog(EntityManagerInterface $entityManager): Response
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                throw new AccessDeniedException('User not authenticated');
            }

            // Get all actions where the current user is the owner
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->select('a', 'acct') // Also select the account to ensure it's loaded
                ->from(Action::class, 'a')
                ->leftJoin('a.account', 'acct') // Use leftJoin to include actions without an account
                ->where('a.owner = :user')
                ->setParameter('user', $user);

            $actions = $queryBuilder->getQuery()->getResult();

            // Split actions into open and closed
            $openActions = [];
            $closedActions = [];

            foreach ($actions as $action) {
                if ($action->isClosed()) {
                    $closedActions[] = $action;
                } else {
                    $openActions[] = $action;
                }
            }

            // Sort open actions by nextStepDate ASC (earliest dates first)
            usort($openActions, function($a, $b) {
                $dateA = $a->getNextStepDate() ?: new \DateTime('9999-12-31');
                $dateB = $b->getNextStepDate() ?: new \DateTime('9999-12-31');
                return $dateA <=> $dateB;
            });

            // Sort closed actions by dateClosed DESC (most recently closed first)
            usort($closedActions, function($a, $b) {
                $dateA = $a->getDateClosed() ?: new \DateTime('1970-01-01');
                $dateB = $b->getDateClosed() ?: new \DateTime('1970-01-01');
                return $dateB <=> $dateA;
            });

            // Combine the sorted lists (open actions first, then closed actions)
            $actions = array_merge($openActions, $closedActions);

            // Prepare actions for the template
            $userBacklogActions = [];
            foreach ($actions as $action) {
                $account = $action->getAccount();
                $accountId = $account ? $account->getId() : null;

                // Get the closure date from the History entity if the action is closed
                $dateClosed = null;
                if ($action->isClosed()) {
                    // Find the history entry that corresponds to when the action was closed
                    $histories = $action->getHistories();
                    foreach ($histories as $history) {
                        if (strpos($history->getNote(), 'closed') !== false) {
                            $dateClosed = $this->appSettingsService->formatDateTime($history->getCreatedAt());
                            break;
                        }
                    }

                    // If no history entry is found, fall back to the action's dateClosed field
                    if ($dateClosed === null && $action->getDateClosed()) {
                        $dateClosed = $this->appSettingsService->formatDateTime($action->getDateClosed());
                    }
                }

                $userBacklogActions[] = [
                    'id' => $action->getId(),
                    'accountId' => $accountId,
                    'accountName' => $account ? $account->getName() : 'N/A',
                    'accountStatus' => $account ? $account->getStatus() : true, // Include account status (true = active, false = disabled)
                    'lastAction' => $action->getTitle(),
                    'title' => $action->getTitle(),
                    'contact' => $action->getContact(),
                    'nextStepDateFormatted' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
                    'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null,
                    'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
                    'closed' => $action->isClosed(),
                    'dateClosed' => $dateClosed,
                    'notes' => $action->getNotes(),
                    'hasNotes' => !empty($action->getNotes())
                ];
            }

            return $this->render('user/my_backlog.html.twig', [
                'userBacklogActions' => $userBacklogActions,
                'username' => $user->getUsername()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while fetching your backlog: ' . $e->getMessage());
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/users/{userId}/account-actions', name: 'app_user_account_actions', methods: ['GET'])]
    public function getUserAccountActions(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();
        try {
            $user = $entityManager->getRepository(User::class)->find($userId);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Get actions where the user is the owner
            // Order by closed ASC (open actions first) and then by nextStepDate ASC (earliest dates first)
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->select('a', 'acct') // Also select the account to ensure it's loaded
            ->from(Action::class, 'a')
                ->leftJoin('a.account', 'acct') // Use leftJoin to include actions without an account
                ->where('a.owner = :user')
                ->orderBy('a.closed', 'ASC')
                ->addOrderBy('a.nextStepDate', 'ASC')
                ->setParameter('user', $user);

            $actions = $queryBuilder->getQuery()->getResult();

            if (empty($actions)) {
                return new JsonResponse([]);
            }

            // Count non-empty notes per account
            $accountNotesCount = [];
            foreach ($actions as $action) {
                $account = $action->getAccount();
                $accountId = $account ? $account->getId() : 'N/A';

                // Only count non-empty notes
                if (!empty($action->getNotes())) {
                    if (!isset($accountNotesCount[$accountId])) {
                        $accountNotesCount[$accountId] = 0;
                    }
                    $accountNotesCount[$accountId]++;
                }
            }

            $accountActions = [];
            foreach ($actions as $action) {
                $account = $action->getAccount();
                $accountId = $account ? $account->getId() : 'N/A';

                // Get the closure date from the History entity if the action is closed
                $dateClosed = null;
                if ($action->isClosed()) {
                    // Find the history entry that corresponds to when the action was closed
                    $histories = $action->getHistories();
                    foreach ($histories as $history) {
                        if (strpos($history->getNote(), 'closed') !== false) {
                            $dateClosed = $this->appSettingsService->formatDateTime($history->getCreatedAt());
                            break;
                        }
                    }

                    // If no history entry is found, fall back to the action's dateClosed field
                    if ($dateClosed === null && $action->getDateClosed()) {
                        $dateClosed = $this->appSettingsService->formatDateTime($action->getDateClosed());
                    }
                }

                $accountActions[] = [
                    'id' => $action->getId(),
                    'accountId' => $accountId,
                    'accountName' => $account ? $account->getName() : 'N/A',
                    'accountStatus' => $account ? $account->getStatus() : true, // Include account status (true = active, false = disabled)
                    'lastAction' => $action->getTitle(),
                    'contact' => $action->getContact(), // Ensure contact is included
                    'nextStepDateFormatted' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null, // For display
                    'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                    'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                    'closed' => $action->isClosed(),
                    'dateClosed' => $dateClosed,
                    'notes' => $action->getNotes(),
                    'hasNotes' => !empty($action->getNotes()),
                    'accountNotesCount' => isset($accountNotesCount[$accountId]) ? $accountNotesCount[$accountId] : 0
                ];
            }

            return new JsonResponse($accountActions);
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Error fetching user account actions: ' . $e->getMessage());
            return new JsonResponse(['error' => 'An error occurred while fetching account actions.'], 500);
        }
    }

    #[Route('/users/{userId}/create-action', name: 'app_create_user_action', methods: ['POST'])]
    public function createAction(int $userId, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();
        // Validate CSRF token
        $token = $request->request->get('_token');
        if (!$token) {
            return new JsonResponse(['error' => 'CSRF token is missing'], 400);
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('create_action', $token))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        // Find the user
        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Get the owner from the request or use the current user as fallback
        $ownerId = $request->request->get('owner');
        $owner = null;

        if ($ownerId) {
            $owner = $entityManager->getRepository(User::class)->find($ownerId);
        }

        if (!$owner) {
            $owner = $this->getUser();
        }

        // Create a new action
        $action = new Action();
        $action->setTitle($request->request->get('title'));

        // Handle next step date
        $nextStepDate = $request->request->get('nextStepDate');
        if ($nextStepDate) {
            $dateTime = new \DateTime($nextStepDate);
            $action->setNextStepDate($dateTime);
        }

        // Set owner relationship
        $action->setOwner($owner);

        // Save to database
        $entityManager->persist($action);
        $entityManager->flush();

        // Return the new action data
        return new JsonResponse([
            'id' => $action->getId(),
            'title' => $action->getTitle(),
            'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
            'createdAt' => $this->appSettingsService->formatDateTime($action->getCreatedAt()),
            'owner' => $action->getOwner()->getUsername()
        ]);
    }

    #[Route('/users/{userId}/update', name: 'app_update_user', methods: ['POST'])]
    public function updateUser(int $userId, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();
        try {
            // Validate CSRF token
            $token = $request->request->get('_token');
            if (!$token) {
                return new JsonResponse(['error' => 'CSRF token is missing'], 400);
            }

            if (!$csrfTokenManager->isTokenValid(new CsrfToken('update_user', $token))) {
                return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
            }

            // Find the user
            $user = $entityManager->getRepository(User::class)->find($userId);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Update user fields
            $fieldName = $request->request->get('field');
            $value = $request->request->get('value');

            if (!$fieldName) {
                return new JsonResponse(['error' => 'Field name is required'], 400);
            }

            // Validate and update the field
            switch ($fieldName) {
                case 'username':
                    if (strlen($value) >= 2 && strlen($value) <= 180) {
                        // Check if username is already in use by another user
                        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $value]);
                        if ($existingUser && $existingUser->getId() !== $user->getId()) {
                            return new JsonResponse(['error' => 'Username is already in use'], 400);
                        }
                        $user->setUsername($value);
                    } else {
                        return new JsonResponse(['error' => 'Username must be between 2 and 180 characters'], 400);
                    }
                    break;
                case 'email':
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        // Check if email is already in use by another user
                        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $value]);
                        if ($existingUser && $existingUser->getId() !== $user->getId()) {
                            return new JsonResponse(['error' => 'Email is already in use'], 400);
                        }
                        $user->setEmail($value);
                    } else {
                        return new JsonResponse(['error' => 'Invalid email format'], 400);
                    }
                    break;
                case 'roles':
                    // Convert comma-separated roles to array
                    $roles = array_map('trim', explode(',', $value));
                    // Filter out empty values
                    $roles = array_filter($roles);
                    // Ensure ROLE_USER is always present
                    if (!in_array('ROLE_USER', $roles)) {
                        $roles[] = 'ROLE_USER';
                    }
                    $user->setRoles($roles);
                    break;
                case 'is_2fa_enabled':
                    $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($boolValue !== null) {
                        $user->setIs2faEnabled($boolValue);
                    } else {
                        return new JsonResponse(['error' => 'Value must be a boolean'], 400);
                    }
                    break;
                case 'secret_2fa':
                    $user->setSecret2fa($value);
                    break;
                default:
                    return new JsonResponse(['error' => 'Invalid field name'], 400);
            }

            // Use QueryBuilder to ensure SQL logging for the update
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->update(User::class, 'u')
                ->where('u.id = :id')
                ->setParameter('id', $user->getId());

            // Set the appropriate field based on which one was updated
            switch ($fieldName) {
                case 'username':
                    $queryBuilder->set('u.username', ':value')
                        ->setParameter('value', $value);
                    break;
                case 'email':
                    $queryBuilder->set('u.email', ':value')
                        ->setParameter('value', $value);
                    break;
                case 'roles':
                    $queryBuilder->set('u.roles', ':value')
                        ->setParameter('value', json_encode($user->getRoles())); // Convert roles array to JSON string
                    break;
                case 'is_2fa_enabled':
                    $queryBuilder->set('u.is_2fa_enabled', ':value')
                        ->setParameter('value', $user->isIs2faEnabled());
                    break;
                case 'secret_2fa':
                    $queryBuilder->set('u.secret_2fa', ':value')
                        ->setParameter('value', $user->getSecret2fa());
                    break;
            }

            // Execute the update query
            $queryBuilder->getQuery()->execute();

            // Send account updated email
            try {
                $this->emailService->sendAccountUpdatedEmail(
                    $user->getEmail(),
                    $user->getUsername()
                );
            } catch (\Exception $e) {
                // Log the error but don't prevent user update
                error_log(sprintf(
                    'Failed to send account updated email to user ID %d (%s): %s',
                    $user->getId(),
                    $user->getEmail(),
                    $e->getMessage()
                ));
            }

            // Return success response
            return new JsonResponse([
                'success' => true,
                'message' => 'User updated successfully',
                'field' => $fieldName,
                'value' => $fieldName === 'roles' ? implode(', ', $user->getRoles()) : $value
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while updating user: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/user/backlog/update-action-field/{id}', name: 'app_user_backlog_update_action_field', methods: ['POST'])]
    public function updateBacklogActionField(int $id, Request $request, EntityManagerInterface $entityManager, AppSettingsService $appSettingsService, ActionHistoryService $actionHistoryService): JsonResponse
    {
        // Get the current user
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Find the action
        $action = $entityManager->getRepository(Action::class)->find($id);

        if (!$action) {
            return new JsonResponse(['error' => 'Action not found'], 404);
        }

        // All users can update any action (removed permission check)

        try {
            // Get field and value from request
            $fieldName = $request->request->get('fieldName');
            $newValue = $request->request->get('newValue');

            if (!$fieldName) {
                return new JsonResponse(['error' => 'Field name is required'], 400);
            }

            // Update the appropriate field based on fieldName
            switch ($fieldName) {
                case 'contact':
                    // Allow any contact value, including "No contacts available" and removed contacts
                    // This is necessary to handle cases where contacts have been removed from the account
                    // or when the user wants to set "No contacts available"
                    $action->setContact($newValue);
                    break;
                case 'action':
                    $action->setTitle($newValue);
                    break;
                case 'account':
                    // For account, we need to find the account entity by name
                    $account = $entityManager->getRepository(\App\Entity\Account::class)->findOneBy(['name' => $newValue]);
                    if (!$account) {
                        // If account doesn't exist, create a new one with basic info
                        $account = new \App\Entity\Account();
                        $account->setName($newValue);
                        $entityManager->persist($account);
                    }
                    $action->setAccount($account);
                    break;
                case 'date':
                    try {
                        // Parse and set the new date
                        $dateTime = new \DateTime($newValue);
                        $action->setNextStepDate($dateTime);
                    } catch (\Exception $e) {
                        return new JsonResponse(['error' => 'Invalid date format'], 400);
                    }
                    break;
                default:
                    return new JsonResponse(['error' => 'Invalid field name'], 400);
            }

            // Save to database
            $entityManager->flush();

            // Create history entry
            $actionHistoryService->createHistoryEntry($action);

            // Flush again to save the history entry
            $entityManager->flush();

            // Return the updated action data
            return new JsonResponse([
                'id' => $action->getId(),
                'title' => $action->getTitle(),
                'accountName' => $action->getAccount() ? $action->getAccount()->getName() : 'N/A',
                'accountId' => $action->getAccount() ? $action->getAccount()->getId() : null,
                'lastAction' => $action->getTitle(),
                'contact' => $action->getContact(),
                'nextStepDateFormatted' => $action->getNextStepDate() ? $appSettingsService->formatDate($action->getNextStepDate()) : null, // For display
                'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                'nextStepDate' => $action->getNextStepDate() ? $appSettingsService->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                'createdAt' => $action->getCreatedAt() ? $appSettingsService->formatDateTime($action->getCreatedAt()) : null,
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown',
                'ownerId' => $action->getOwner() ? $action->getOwner()->getId() : null,
                'closed' => $action->isClosed(),
                'dateClosed' => $action->getDateClosed() ? $appSettingsService->formatDateTime($action->getDateClosed()) : null,
                'notes' => $action->getNotes(),
                'hasNotes' => !empty($action->getNotes())
            ]);
        } catch (\Exception $e) {
            // Log the detailed error
            error_log('Error updating action field: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());

            // Return error response
            return new JsonResponse(['error' => 'Error updating field. Please try again.'], 400);
        }
    }

    #[Route('/users/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Only allow administrators to access this page
        $this->denyAccessUnlessAdmin();

        // Main user form
        $form = $this->createForm(\App\Form\UserType::class, $user);
        $form->handleRequest($request);

        // Password change form
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        // 2FA form
        $twoFactorForm = $this->createForm(TwoFactorAuthType::class, $user);
        $twoFactorForm->handleRequest($request);

        // Handle main form submission
        if ($form->isSubmitted() && $form->isValid()) {
            // Use QueryBuilder to ensure SQL logging for the update
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->update(User::class, 'u')
                ->set('u.username', ':username')
                ->set('u.email', ':email')
                ->set('u.roles', ':roles')
                ->set('u.disabled', ':disabled')
                ->where('u.id = :id')
                ->setParameter('username', $user->getUsername())
                ->setParameter('email', $user->getEmail())
                ->setParameter('roles', json_encode($user->getRoles())) // Convert roles array to JSON string
                ->setParameter('disabled', $user->isDisabled())
                ->setParameter('id', $user->getId());

            // Execute the update query
            $queryBuilder->getQuery()->execute();

            // Ensure changes are persisted to the database
            $entityManager->flush();

            // Send account updated email
            $emailSent = true;
            try {
                $this->emailService->sendAccountUpdatedEmail(
                    $user->getEmail(),
                    $user->getUsername()
                );
            } catch (\Exception $e) {
                // Log the error but don't prevent user update
                error_log(sprintf(
                    'Failed to send account updated email to user ID %d (%s): %s',
                    $user->getId(),
                    $user->getEmail(),
                    $e->getMessage()
                ));
                $emailSent = false;
            }

            if ($emailSent) {
                $this->addFlash('success', sprintf('User "%s" updated successfully. A notification email has been sent.', $user->getUsername()));
            } else {
                $this->addFlash('warning', sprintf('Warning: User "%s" was updated, but the notification email could not be sent. Please check the system logs.', $user->getUsername()));
            }

            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        // Handle password form submission
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();

            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
            }

            // Set new password
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $entityManager->flush();

            // Send account updated email
            $emailSent = true;
            try {
                $this->emailService->sendAccountUpdatedEmail(
                    $user->getEmail(),
                    $user->getUsername()
                );
            } catch (\Exception $e) {
                // Log the error but don't prevent user update
                error_log(sprintf(
                    'Failed to send account updated email to user ID %d (%s): %s',
                    $user->getId(),
                    $user->getEmail(),
                    $e->getMessage()
                ));
                $emailSent = false;
            }

            if ($emailSent) {
                $this->addFlash('success', sprintf('Password for user "%s" updated successfully. A notification email has been sent.', $user->getUsername()));
            } else {
                $this->addFlash('warning', sprintf('Warning: Password for user "%s" was updated, but the notification email could not be sent. Please check the system logs.', $user->getUsername()));
            }

            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        // Handle 2FA form submission
        if ($twoFactorForm->isSubmitted() && $twoFactorForm->isValid()) {
            // If 2FA was enabled and is now being disabled, clear the secret
            if (!$user->isIs2faEnabled()) {
                $user->setSecret2fa(null);
            }

            $entityManager->flush();

            // Send account updated email
            $emailSent = true;
            try {
                $this->emailService->sendAccountUpdatedEmail(
                    $user->getEmail(),
                    $user->getUsername()
                );
            } catch (\Exception $e) {
                // Log the error but don't prevent user update
                error_log(sprintf(
                    'Failed to send account updated email to user ID %d (%s): %s',
                    $user->getId(),
                    $user->getEmail(),
                    $e->getMessage()
                ));
                $emailSent = false;
            }

            if ($emailSent) {
                $this->addFlash('success', sprintf('2FA settings for user "%s" updated successfully. A notification email has been sent.', $user->getUsername()));
            } else {
                $this->addFlash('warning', sprintf('Warning: 2FA settings for user "%s" were updated, but the notification email could not be sent. Please check the system logs.', $user->getUsername()));
            }

            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'passwordForm' => $passwordForm->createView(),
            'twoFactorForm' => $twoFactorForm->createView(),
        ]);
    }

    #[Route('/users/{id}/reset-password', name: 'app_user_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();
        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reset-password-' . $user->getId(), $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token. Please try again.');
            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        // Generate a secure temporary password (12 characters)
        $temporaryPassword = $this->generateSecurePassword(12);

        // Hash and set the new password
        $hashedPassword = $passwordHasher->hashPassword($user, $temporaryPassword);
        $user->setPassword($hashedPassword);

        // Use QueryBuilder to ensure SQL logging for the update
        $queryBuilder = $entityManager->createQueryBuilder();
        $queryBuilder->update(User::class, 'u')
            ->set('u.password', ':password')
            ->where('u.id = :id')
            ->setParameter('password', $hashedPassword)
            ->setParameter('id', $user->getId());

        // Execute the update query
        $queryBuilder->getQuery()->execute();

        // Send password reset email
        $emailSent = true;
        try {
            $this->emailService->sendPasswordResetEmail(
                $user->getEmail(),
                $user->getUsername(),
                $temporaryPassword
            );
        } catch (\Exception $e) {
            // Log the error but don't prevent password reset
            error_log(sprintf(
                'Failed to send password reset email to user ID %d (%s): %s',
                $user->getId(),
                $user->getEmail(),
                $e->getMessage()
            ));
            $emailSent = false;
        }

        // Add appropriate flash message
        if ($emailSent) {
            $this->addFlash('success', sprintf('Password for user "%s" has been reset successfully. A notification email with the temporary password has been sent.', $user->getUsername()));
        } else {
            $this->addFlash('warning', sprintf('Warning: Password for user "%s" has been reset, but the notification email could not be sent. Please check the system logs.', $user->getUsername()));
        }

        return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
    }

    /**
     * Generate a secure random password
     */
    private function generateSecurePassword(int $length = 12): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-_=+';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    #[Route('/users/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleUserStatus(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();

        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle-status-' . $user->getId(), $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token. Please try again.');
            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        // Get the new status (opposite of current status)
        $newStatus = !$user->isDisabled();

        // Update the user's disabled status
        $user->setDisabled($newStatus);

        // Persist the changes to the database
        $entityManager->flush();

        // Add appropriate flash message
        if ($newStatus) {
            $this->addFlash('success', sprintf('User "%s" has been disabled successfully.', $user->getUsername()));
        } else {
            $this->addFlash('success', sprintf('User "%s" has been enabled successfully.', $user->getUsername()));
        }

        return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
    }

    #[Route('/users/{id}/setup-2fa', name: 'app_user_setup_2fa', methods: ['GET', 'POST'])]
    public function setup2fa(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Only allow administrators to access this endpoint
        $this->denyAccessUnlessAdmin();
        // Check if 2FA is already enabled
        if ($user->isIs2faEnabled() && $user->getSecret2fa()) {
            $this->addFlash('info', '2FA is already enabled for this account.');
            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        // Generate a new secret if one doesn't exist
        if (!$user->getSecret2fa()) {
            // Generate a proper base32 encoded secret for TOTP
            $randomBytes = random_bytes(20); // 20 bytes = 160 bits (recommended for TOTP)
            $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 character set

            // Convert random bytes to base32 string
            $secret = '';
            $bits = '';

            // Convert bytes to binary string
            for ($i = 0; $i < strlen($randomBytes); $i++) {
                $bits .= sprintf('%08b', ord($randomBytes[$i]));
            }

            // Convert 5 bits at a time to base32 characters
            for ($i = 0; $i + 5 <= strlen($bits); $i += 5) {
                $chunk = substr($bits, $i, 5);
                $secret .= $base32Chars[bindec($chunk)];
            }

            // Make sure the secret is a multiple of 8 characters (padding)
            while (strlen($secret) % 8 !== 0) {
                $secret .= '=';
            }

            $user->setSecret2fa($secret);

            // Use QueryBuilder to ensure SQL logging for the update
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->update(User::class, 'u')
                ->set('u.secret_2fa', ':secret')
                ->where('u.id = :id')
                ->setParameter('secret', $secret)
                ->setParameter('id', $user->getId());

            // Execute the update query
            $queryBuilder->getQuery()->execute();

            // Send account updated email
            try {
                $this->emailService->sendAccountUpdatedEmail(
                    $user->getEmail(),
                    $user->getUsername()
                );
            } catch (\Exception $e) {
                // Log the error but don't prevent user update
                error_log(sprintf(
                    'Failed to send account updated email to user ID %d (%s): %s',
                    $user->getId(),
                    $user->getEmail(),
                    $e->getMessage()
                ));
            }
        }

        // Handle verification code submission
        if ($request->isMethod('POST')) {
            $code = $request->request->get('verification_code');

            // Verify the TOTP code against the secret
            if ($this->verifyTotpCode($user->getSecret2fa(), $code)) {
                $user->setIs2faEnabled(true);

                // Use QueryBuilder to ensure SQL logging for the update
                $queryBuilder = $entityManager->createQueryBuilder();
                $queryBuilder->update(User::class, 'u')
                    ->set('u.is_2fa_enabled', ':enabled')
                    ->where('u.id = :id')
                    ->setParameter('enabled', true)
                    ->setParameter('id', $user->getId());

                // Execute the update query
                $queryBuilder->getQuery()->execute();

                // Send account updated email
                try {
                    $this->emailService->sendAccountUpdatedEmail(
                        $user->getEmail(),
                        $user->getUsername()
                    );
                } catch (\Exception $e) {
                    // Log the error but don't prevent user update
                    error_log(sprintf(
                        'Failed to send account updated email to user ID %d (%s): %s',
                        $user->getId(),
                        $user->getEmail(),
                        $e->getMessage()
                    ));
                }

                $this->addFlash('success', '2FA has been successfully enabled.');
                return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
            } else {
                $this->addFlash('error', 'Invalid verification code. Please try again.');
            }
        }

        // Get the application name from settings or use a default
        $appName = 'DPCRM';

        // Create a properly formatted otpauth URI
        // Format: otpauth://totp/[App Name]:[Account]?secret=[Secret]&issuer=[App Name]&algorithm=SHA1&digits=6&period=30
        $otpauthUri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            urlencode($appName),
            urlencode($user->getUsername()),
            $user->getSecret2fa(),
            urlencode($appName)
        );

        // Generate QR code URL using the otpauth URI
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpauthUri);

        return $this->render('user/setup_2fa.html.twig', [
            'user' => $user,
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $user->getSecret2fa(),
            'appName' => $appName
        ]);
    }
}

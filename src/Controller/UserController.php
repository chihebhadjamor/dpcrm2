<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Action;
use App\Service\AppSettingsService;
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

class UserController extends AbstractWebController
{
    private AppSettingsService $appSettingsService;

    public function __construct(AppSettingsService $appSettingsService)
    {
        $this->appSettingsService = $appSettingsService;
    }
    #[Route('/users/create-ajax', name: 'app_create_user_ajax', methods: ['POST'])]
    public function createUserAjax(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
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

        // Return the created user data
        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ]);
    }

    #[Route('/users', name: 'app_users')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findBy([], ['id' => 'DESC']);

        // Return the view with users
        return $this->render('user/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/api/users', name: 'app_get_users', methods: ['GET'])]
    public function getUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername()
            ];
        }

        return new JsonResponse($usersData);
    }

    #[Route('/users/{userId}/actions', name: 'app_user_actions', methods: ['GET'])]
    public function getUserActions(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
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
                $actionsData[] = [
                    'id' => $action->getId(),
                    'title' => $action->getTitle(),
                    'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
                    'createdAt' => $this->appSettingsService->formatDateTime($action->getCreatedAt()),
                    'owner' => $action->getOwner()->getUsername(),
                    'account' => $action->getAccount() ? $action->getAccount()->getName() : null,
                    'closed' => $action->isClosed(),
                    'dateClosed' => $action->getDateClosed() ? $this->appSettingsService->formatDateTime($action->getDateClosed()) : null
                ];
            }

            return new JsonResponse($actionsData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while fetching actions: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/users/{userId}/account-actions', name: 'app_user_account_actions', methods: ['GET'])]
    public function getUserAccountActions(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
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

                $accountActions[] = [
                    'id' => $action->getId(),
                    'accountId' => $accountId,
                    'accountName' => $account ? $account->getName() : 'N/A',
                    'lastAction' => $action->getTitle(),
                    'contact' => $action->getContact(), // Ensure contact is included
                    'nextStepDateFormatted' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null, // For display
                    'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                    'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                    'closed' => $action->isClosed(),
                    'dateClosed' => $action->getDateClosed() ? $this->appSettingsService->formatDateTime($action->getDateClosed()) : null,
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

            // Save to database
            $entityManager->flush();

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
    public function updateBacklogActionField(int $id, Request $request, EntityManagerInterface $entityManager, AppSettingsService $appSettingsService): JsonResponse
    {
        // Find the action
        $action = $entityManager->getRepository(Action::class)->find($id);

        if (!$action) {
            return new JsonResponse(['error' => 'Action not found'], 404);
        }

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

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // You can add security checks here, e.g., $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(\App\Form\UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('app_users', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}

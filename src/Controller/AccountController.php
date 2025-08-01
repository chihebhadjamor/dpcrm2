<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Action;
use App\Entity\User;
use App\Form\AccountType;
use App\Service\ActionHistoryService;
use App\Service\AppSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormFactoryInterface;

class AccountController extends AbstractWebController
{
    private FormFactoryInterface $formFactory;
    private AppSettingsService $appSettingsService;

    public function __construct(FormFactoryInterface $formFactory, AppSettingsService $appSettingsService)
    {
        $this->formFactory = $formFactory;
        $this->appSettingsService = $appSettingsService;
    }
    #[Route('/accounts/{id}/edit', name: 'app_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $form = $this->formFactory->create(AccountType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Account updated successfully.');

            return $this->redirectToRoute('app_accounts', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/edit.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
        ]);
    }
    // Removed duplicate route that conflicts with UserController::getUsers
    #[Route('/accounts/{id}/update', name: 'app_update_account', methods: ['POST'])]
    public function updateAccount(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Find the account
        $account = $entityManager->getRepository(Account::class)->find($id);

        if (!$account) {
            return new JsonResponse(['error' => 'Account not found'], 404);
        }

        // Update account fields
        $fieldName = $request->request->get('field');
        $value = $request->request->get('value');

        // Validate and update the field
        switch ($fieldName) {
            case 'name':
                if (strlen($value) >= 2 && strlen($value) <= 255) {
                    $account->setName($value);
                } else {
                    return new JsonResponse(['error' => 'Name must be between 2 and 255 characters'], 400);
                }
                break;
            default:
                return new JsonResponse(['error' => 'Invalid field name'], 400);
        }

        // Save to database
        $entityManager->flush();

        // Return success response
        return new JsonResponse([
            'success' => true,
            'message' => 'Account updated successfully',
            'field' => $fieldName,
            'value' => $value
        ]);
    }

    #[Route('/accounts', name: 'app_accounts')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $accounts = $entityManager->getRepository(Account::class)->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        // Fetch the earliest upcoming (future) action for each account
        $nextActions = [];
        // Store contact information for each account
        $contactInfo = [];
        // Store owner information for each account
        $ownerInfo = [];

        if (!empty($accounts)) {
            // Get all account IDs
            $accountIds = array_map(function($account) {
                return $account->getId();
            }, $accounts);

            // Use Doctrine's query builder to get all open actions
            $qb = $entityManager->createQueryBuilder();
            $qb->select('a', 'acc', 'u')
               ->from(Action::class, 'a')
               ->join('a.account', 'acc')
               ->join('a.owner', 'u')
               ->where('acc.id IN (:accountIds)')
               ->andWhere('a.closed = :closed')
               ->andWhere('a.nextStepDate IS NOT NULL')
               ->setParameter('accountIds', $accountIds)
               ->setParameter('closed', false)
               ->orderBy('a.nextStepDate', 'ASC'); // Order by date ascending to get earliest

            $actions = $qb->getQuery()->getResult();

            // Group actions by account ID and keep only the earliest upcoming one
            $tempActions = [];
            foreach ($actions as $action) {
                $accountId = $action->getAccount()->getId();
                if (!isset($tempActions[$accountId])) {
                    $tempActions[$accountId] = $action;
                    // Store contact information from the earliest upcoming action
                    $contactInfo[$accountId] = $action->getContact();
                }
            }

            $nextActions = $tempActions;

            // For accounts without open actions or without contact in open actions,
            // find the most recently closed action with contact information
            foreach ($accountIds as $accountId) {
                if (!isset($contactInfo[$accountId])) {
                    // Use Doctrine's query builder to get the most recently closed action
                    $qbClosed = $entityManager->createQueryBuilder();
                    $qbClosed->select('a')
                       ->from(Action::class, 'a')
                       ->where('a.account = :accountId')
                       ->andWhere('a.closed = :closed')
                       ->setParameter('accountId', $accountId)
                       ->setParameter('closed', true)
                       ->orderBy('a.dateClosed', 'DESC') // Order by date closed descending to get most recent
                       ->setMaxResults(1);

                    $mostRecentClosedAction = $qbClosed->getQuery()->getOneOrNullResult();

                    if ($mostRecentClosedAction) {
                        $contactInfo[$accountId] = $mostRecentClosedAction->getContact();
                    }
                }
            }
        }

        // Create a new account instance for the form
        $account = new Account();

        // Create the form
        $form = $this->formFactory->createBuilder()->setData($account)
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Create Account',
                'attr' => ['class' => 'btn btn-success mt-2']
            ])
            ->getForm();

        // Handle form submission
        return $this->handleForm(
            $request,
            $form,
            function ($data) use ($entityManager, $nextActions, $contactInfo) {
                // Check if an account with this name already exists
                if ($this->accountNameExists($data->getName(), $entityManager)) {
                    // Add a form error
                    $form = $this->formFactory->createBuilder()->setData($data)
                        ->add('name', TextType::class, [
                            'label' => 'Name',
                            'attr' => ['class' => 'form-control'],
                            'invalid_message' => 'An account with this name already exists'
                        ])
                        ->add('save', SubmitType::class, [
                            'label' => 'Create Account',
                            'attr' => ['class' => 'btn btn-success mt-2']
                        ])
                        ->getForm();

                    $form->get('name')->addError(new \Symfony\Component\Form\FormError('An account with this name already exists'));

                    // Return the form with the error
                    return $this->render('account/index.html.twig', [
                        'accounts' => $entityManager->getRepository(Account::class)->findBy([], ['createdAt' => 'DESC']),
                        'nextActions' => $nextActions,
                        'contactInfo' => $contactInfo,
                        'form' => $form->createView(),
                        'duplicate_error' => 'An account with this name already exists'
                    ]);
                }

                // Trim whitespace from the name
                $data->setName(trim($data->getName()));

                // Save to database
                $entityManager->persist($data);
                $entityManager->flush();

                return $this->redirectToRoute('app_accounts');
            },
            'Account created successfully!',
            'There was an error creating the account.',
            'account/index.html.twig',
            [
                'accounts' => $accounts,
                'nextActions' => $nextActions,
                'contactInfo' => $contactInfo
            ]
        );
    }

    #[Route('/accounts/{id}/contacts', name: 'app_account_contacts', methods: ['GET'])]
    public function getAccountContacts(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Get the current user
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Find the account
        $account = $entityManager->getRepository(Account::class)->find($id);

        if (!$account) {
            return new JsonResponse(['error' => 'Account not found'], 404);
        }

        // Get the contacts
        $contacts = $account->getContacts() ?? [];

        // Return the contacts
        return new JsonResponse($contacts);
    }

    #[Route('/accounts/{id}/actions', name: 'app_account_actions')]
    public function getAccountActions(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $account = $entityManager->getRepository(Account::class)->find($id);

        if (!$account) {
            return new JsonResponse(['error' => 'Account not found'], 404);
        }

        // Use query builder to order by closed ASC (open actions first) and then by nextStepDate ASC (earliest dates first)
        $queryBuilder = $entityManager->createQueryBuilder();
        $queryBuilder->select('a')
            ->from(Action::class, 'a')
            ->where('a.account = :account')
            ->orderBy('a.closed', 'ASC')
            ->addOrderBy('a.nextStepDate', 'ASC')
            ->setParameter('account', $account);

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

            // Check if the action has more than one history record
            $historyCount = $action->getActionHistories()->count();
            $hasHistory = $historyCount > 1;

            $actionsData[] = [
                'id' => $action->getId(),
                'title' => $action->getTitle(),
                'contact' => $action->getContact(),
                'nextStepDateFormatted' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null, // For display
                'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                'createdAt' => $this->appSettingsService->formatDateTime($action->getCreatedAt()),
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown',
                'closed' => $action->isClosed(),
                'dateClosed' => $dateClosed,
                'notes' => $action->getNotes(),
                'hasNotes' => !empty($action->getNotes()),
                'hasHistory' => $hasHistory
            ];
        }

        return new JsonResponse($actionsData);
    }

    /**
     * Check if an account with the given name already exists
     *
     * @param string $name The account name to check
     * @param EntityManagerInterface $entityManager The entity manager
     * @return bool True if an account with the name exists, false otherwise
     */
    private function accountNameExists(string $name, EntityManagerInterface $entityManager): bool
    {
        // Trim the name and convert to lowercase for case-insensitive comparison
        $normalizedName = strtolower(trim($name));

        // Use DQL for a case-insensitive search
        $query = $entityManager->createQuery(
            'SELECT COUNT(a) FROM App\Entity\Account a WHERE LOWER(a.name) = :name'
        )->setParameter('name', $normalizedName);

        return (bool)$query->getSingleScalarResult();
    }

    #[Route('/accounts/create-ajax', name: 'app_create_account_ajax', methods: ['POST'])]
    public function createAccountAjax(Request $request, EntityManagerInterface $entityManager, ActionHistoryService $actionHistoryService): JsonResponse
    {
        // Get form data
        $name = $request->request->get('name');
        $contact = $request->request->get('contact');
        $ownerId = $request->request->get('owner');

        // Validate required fields
        if (!$name) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        // Check if an account with this name already exists
        if ($this->accountNameExists($name, $entityManager)) {
            return new JsonResponse(['error' => 'An account with this name already exists'], 400);
        }

        // Create a new account
        $account = new Account();
        $account->setName(trim($name)); // Trim whitespace from the name

        // If a contact is provided, add it to the account's contacts
        if ($contact) {
            $account->addContact($contact);
        }

        // Save to database
        $entityManager->persist($account);
        $entityManager->flush();

        // Create an initial action for this account if owner is provided
        $actionOwner = null;
        if ($ownerId) {
            $owner = $entityManager->getRepository(User::class)->find($ownerId);
            if ($owner) {
                $action = new Action();
                $action->setTitle('Initial contact');
                $action->setContact($contact);
                $action->setAccount($account);
                $action->setOwner($owner);

                $entityManager->persist($action);
                $entityManager->flush();

                // Create initial history entry for the action
                $actionHistoryService->createHistoryEntry($action);
                $entityManager->flush();

                $actionOwner = $owner->getUsername();
            }
        }

        // Return the created account data
        return new JsonResponse([
            'id' => $account->getId(),
            'name' => $account->getName(),
            'contact' => $contact,
            'actionOwner' => $actionOwner
        ]);
    }

    #[Route('/accounts/{id}/create-action', name: 'app_create_account_action', methods: ['POST'])]
    public function createAction(int $id, Request $request, EntityManagerInterface $entityManager, ActionHistoryService $actionHistoryService): JsonResponse
    {
        // Find the account
        $account = $entityManager->getRepository(Account::class)->find($id);

        if (!$account) {
            return new JsonResponse(['error' => 'Account not found'], 404);
        }

        // Check if account is disabled
        if (!$account->isActive()) {
            return new JsonResponse(['error' => 'Cannot create actions for a disabled account'], 403);
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

        if (!$owner) {
            return new JsonResponse(['error' => 'Owner is required'], 400);
        }

        // Get the contact from the request
        $contact = $request->request->get('contact');
        if (!$contact) {
            return new JsonResponse(['error' => 'Contact is required'], 400);
        }

        // Create a new action
        $action = new Action();
        $action->setTitle($request->request->get('title'));
        $action->setContact($contact);
        $action->setCreatedAt(new \DateTime()); // Explicitly set createdAt

        // Handle next step date
        $nextStepDate = $request->request->get('nextStepDate');
        if ($nextStepDate) {
            try {
                $dateTime = new \DateTime($nextStepDate);
                $action->setNextStepDate($dateTime);

            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format for nextStepDate'], 400);
            }
        }

        // Set relationships
        $action->setAccount($account);
        $action->setOwner($owner);

        try {
            // Save to database
            $entityManager->persist($action);
            $entityManager->flush();

            // Create initial history entry for the action
            $actionHistoryService->createHistoryEntry($action);
            $entityManager->flush();

            // Return the new action data
            return new JsonResponse([
                'id' => $action->getId(),
                'title' => $action->getTitle(),
                'contact' => $action->getContact(),
                'nextStepDateFormatted' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null, // For display
                'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                'nextStepDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                'createdAt' => $this->appSettingsService->formatDateTime($action->getCreatedAt()),
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown',
                'closed' => $action->isClosed(),
                'dateClosed' => $action->getDateClosed() ? $this->appSettingsService->formatDateTime($action->getDateClosed()) : null,
                'notes' => $action->getNotes(),
                'hasNotes' => !empty($action->getNotes())
            ]);
        } catch (\Exception $e) {
            // Log the detailed error
            error_log('Error creating action: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());

            // Return error response
            return new JsonResponse(['error' => 'Error creating action. Please try again.'], 400);
        }
    }

    #[Route('/accounts/list-ajax', name: 'app_accounts_list_ajax', methods: ['GET'])]
    public function getAccountsList(EntityManagerInterface $entityManager): JsonResponse
    {
        $accounts = $entityManager->getRepository(Account::class)->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        // Fetch the earliest upcoming (future) action for each account
        $nextActions = [];
        // Store contact information for each account
        $contactInfo = [];

        if (!empty($accounts)) {
            // Get all account IDs
            $accountIds = array_map(function($account) {
                return $account->getId();
            }, $accounts);

            // Use Doctrine's query builder to get all open actions
            $qb = $entityManager->createQueryBuilder();
            $qb->select('a', 'acc', 'u')
               ->from(Action::class, 'a')
               ->join('a.account', 'acc')
               ->join('a.owner', 'u')
               ->where('acc.id IN (:accountIds)')
               ->andWhere('a.closed = :closed')
               ->andWhere('a.nextStepDate IS NOT NULL')
               ->setParameter('accountIds', $accountIds)
               ->setParameter('closed', false)
               ->orderBy('a.nextStepDate', 'ASC'); // Order by date ascending to get earliest

            $actions = $qb->getQuery()->getResult();

            // Group actions by account ID and keep only the earliest upcoming one
            $tempActions = [];
            foreach ($actions as $action) {
                $accountId = $action->getAccount()->getId();
                if (!isset($tempActions[$accountId])) {
                    $tempActions[$accountId] = $action;
                    // Store contact information from the earliest upcoming action
                    $contactInfo[$accountId] = $action->getContact();
                }
            }

            $nextActions = $tempActions;

            // For accounts without open actions or without contact in open actions,
            // find the most recently closed action with contact information
            foreach ($accountIds as $accountId) {
                if (!isset($contactInfo[$accountId])) {
                    // Use Doctrine's query builder to get the most recently closed action
                    $qbClosed = $entityManager->createQueryBuilder();
                    $qbClosed->select('a')
                       ->from(Action::class, 'a')
                       ->where('a.account = :accountId')
                       ->andWhere('a.closed = :closed')
                       ->setParameter('accountId', $accountId)
                       ->setParameter('closed', true)
                       ->orderBy('a.dateClosed', 'DESC') // Order by date closed descending to get most recent
                       ->setMaxResults(1);

                    $mostRecentClosedAction = $qbClosed->getQuery()->getOneOrNullResult();

                    if ($mostRecentClosedAction) {
                        $contactInfo[$accountId] = $mostRecentClosedAction->getContact();
                    }
                }
            }
        }

        // Prepare the accounts data for JSON response
        $accountsData = [];
        foreach ($accounts as $account) {
            $accountId = $account->getId();
            $nextAction = isset($nextActions[$accountId]) ? $nextActions[$accountId] : null;

            $accountsData[] = [
                'id' => $accountId,
                'name' => $account->getName(),
                'status' => $account->getStatus(), // Add status field
                'contact' => isset($contactInfo[$accountId]) ? $contactInfo[$accountId] : null,
                'nextStepDateFormatted' => $nextAction ? $this->appSettingsService->formatDate($nextAction->getNextStepDate()) : null, // For display
                'nextStepDateRaw' => $nextAction ? $nextAction->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                'nextStepDate' => $nextAction ? $this->appSettingsService->formatDate($nextAction->getNextStepDate()) : null, // Keep for backward compatibility
                'nextAction' => $nextAction ? $nextAction->getTitle() : null,
                'actionOwner' => $nextAction ? $nextAction->getOwner()->getUsername() : null
            ];
        }

        return new JsonResponse($accountsData);
    }

    #[Route('/actions/{id}/toggle-closed', name: 'app_action_toggle_closed', methods: ['POST'])]
    public function toggleActionClosed(int $id, EntityManagerInterface $entityManager, AppSettingsService $appSettingsService, ActionHistoryService $actionHistoryService): JsonResponse
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

        // For disabled accounts, we only allow closing actions, not reopening them
        $account = $action->getAccount();
        if ($account && !$account->isActive() && $action->isClosed()) {
            return new JsonResponse(['error' => 'Cannot reopen actions for a disabled account'], 403);
        }

        try {
            // Toggle the closed status
            if ($action->isClosed()) {
                $action->reopen();
            } else {
                $action->close();
            }

            // Create history entry for the status change
            $actionHistoryService->createHistoryEntry($action);

            // Save to database
            $entityManager->flush();

            // Return the updated action data
            return new JsonResponse([
                'id' => $action->getId(),
                'title' => $action->getTitle(),
                'accountName' => $action->getAccount() ? $action->getAccount()->getName() : 'N/A',
                'lastAction' => $action->getTitle(),
                'contact' => $action->getContact(),
                'nextStepDateFormatted' => $action->getNextStepDate() ? $appSettingsService->formatDate($action->getNextStepDate()) : null, // For display
                'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                'nextStepDate' => $action->getNextStepDate() ? $appSettingsService->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                'createdAt' => $action->getCreatedAt() ? $appSettingsService->formatDateTime($action->getCreatedAt()) : null,
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown',
                'closed' => $action->isClosed(),
                'dateClosed' => $action->getDateClosed() ? $appSettingsService->formatDateTime($action->getDateClosed()) : null,
                'notes' => $action->getNotes(),
                'hasNotes' => !empty($action->getNotes())
            ]);
        } catch (\Exception $e) {
            // Log the detailed error
            error_log('Error toggling action closed status: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());

            // Return error response
            return new JsonResponse(['error' => 'Error updating action. Please try again.'], 400);
        }
    }

    #[Route('/actions/{id}/close-with-notes', name: 'app_action_close_with_notes', methods: ['POST'])]
    public function closeActionWithNotes(int $id, Request $request, EntityManagerInterface $entityManager, ActionHistoryService $actionHistoryService): JsonResponse
    {
        // Find the action
        $action = $entityManager->getRepository(Action::class)->find($id);

        if (!$action) {
            return new JsonResponse(['error' => 'Action not found'], 404);
        }

        // For disabled accounts, we allow closing actions with notes
        // No restriction needed here since this method only closes actions

        try {
            // Get notes from request
            $notes = $request->request->get('notes');

            // Close the action and set notes
            $action->close();
            $action->setNotes($notes);

            // Create history entry for the status change
            $actionHistoryService->createHistoryEntry($action);

            // Save to database
            $entityManager->flush();

            // Return the updated action data
            return new JsonResponse([
                'id' => $action->getId(),
                'title' => $action->getTitle(),
                'accountName' => $action->getAccount() ? $action->getAccount()->getName() : 'N/A',
                'lastAction' => $action->getTitle(),
                'contact' => $action->getContact(),
                'nextStepDateFormatted' => $action->getNextStepDate() ? (new \App\Service\AppSettingsService())->formatDate($action->getNextStepDate()) : null, // For display
                'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                'nextStepDate' => $action->getNextStepDate() ? (new \App\Service\AppSettingsService())->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                'createdAt' => $action->getCreatedAt()->format('Y-m-d H:i:s'),
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown',
                'closed' => $action->isClosed(),
                'dateClosed' => $action->getDateClosed() ? $action->getDateClosed()->format('Y-m-d H:i:s') : null,
                'notes' => $action->getNotes(),
                'hasNotes' => !empty($action->getNotes())
            ]);
        } catch (\Exception $e) {
            // Log the detailed error
            error_log('Error closing action with notes: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());

            // Return error response
            return new JsonResponse(['error' => 'Error updating action. Please try again.'], 400);
        }
    }

    #[Route('/actions/{id}/update-notes', name: 'app_action_update_notes', methods: ['POST'])]
    public function updateActionNotes(int $id, Request $request, EntityManagerInterface $entityManager, AppSettingsService $appSettingsService, ActionHistoryService $actionHistoryService): JsonResponse
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

        // Check if the action is closed
        if ($action->isClosed()) {
            return new JsonResponse(['error' => 'Cannot modify notes for a closed action'], 403);
        }

        // Check if the account is disabled
        $account = $action->getAccount();
        if ($account && !$account->isActive()) {
            return new JsonResponse(['error' => 'Cannot modify actions for a disabled account'], 403);
        }

        try {
            // Get notes from request
            $notes = $request->request->get('notes');

            // Update the notes
            $action->setNotes($notes);

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
                'lastAction' => $action->getTitle(),
                'contact' => $action->getContact(),
                'nextStepDateFormatted' => $action->getNextStepDate() ? $appSettingsService->formatDate($action->getNextStepDate()) : null, // For display
                'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                'nextStepDate' => $action->getNextStepDate() ? $appSettingsService->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                'createdAt' => $action->getCreatedAt() ? $appSettingsService->formatDateTime($action->getCreatedAt()) : null,
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown',
                'closed' => $action->isClosed(),
                'dateClosed' => $action->getDateClosed() ? $appSettingsService->formatDateTime($action->getDateClosed()) : null,
                'notes' => $action->getNotes(),
                'hasNotes' => !empty($action->getNotes())
            ]);
        } catch (\Exception $e) {
            // Log the detailed error
            error_log('Error updating action notes: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());

            // Return error response
            return new JsonResponse(['error' => 'Error updating notes. Please try again.'], 400);
        }
    }

    #[Route('/actions/{id}/update-date', name: 'app_action_update_date', methods: ['POST'])]
    public function updateActionDate(int $id, Request $request, EntityManagerInterface $entityManager, AppSettingsService $appSettingsService, ActionHistoryService $actionHistoryService): JsonResponse
    {
        // Find the action
        $action = $entityManager->getRepository(Action::class)->find($id);

        if (!$action) {
            return new JsonResponse(['error' => 'Action not found'], 404);
        }

        // Check if the account is disabled
        $account = $action->getAccount();
        if ($account && !$account->isActive()) {
            return new JsonResponse(['error' => 'Cannot modify actions for a disabled account'], 403);
        }

        try {
            // Get date from request
            $date = $request->request->get('date');

            if (!$date) {
                return new JsonResponse(['error' => 'Date is required'], 400);
            }

            try {
                // Parse and set the new date
                $dateTime = new \DateTime($date);
                $action->setNextStepDate($dateTime);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format'], 400);
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
                'lastAction' => $action->getTitle(),
                'contact' => $action->getContact(),
                'nextStepDateFormatted' => $action->getNextStepDate() ? $appSettingsService->formatDate($action->getNextStepDate()) : null, // For display
                'nextStepDateRaw' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null, // For JS logic
                'nextStepDate' => $action->getNextStepDate() ? $appSettingsService->formatDate($action->getNextStepDate()) : null, // Keep for backward compatibility
                'createdAt' => $action->getCreatedAt() ? $appSettingsService->formatDateTime($action->getCreatedAt()) : null,
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown',
                'closed' => $action->isClosed(),
                'dateClosed' => $action->getDateClosed() ? $appSettingsService->formatDateTime($action->getDateClosed()) : null,
                'notes' => $action->getNotes(),
                'hasNotes' => !empty($action->getNotes())
            ]);
        } catch (\Exception $e) {
            // Log the detailed error
            error_log('Error updating action date: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());

            // Return error response
            return new JsonResponse(['error' => 'Error updating date. Please try again.'], 400);
        }
    }

    #[Route('/actions/{id}/update-field', name: 'app_action_update_field', methods: ['POST'])]
    public function updateActionField(int $id, Request $request, EntityManagerInterface $entityManager, AppSettingsService $appSettingsService, ActionHistoryService $actionHistoryService): JsonResponse
    {
        // Find the action
        $action = $entityManager->getRepository(Action::class)->find($id);

        if (!$action) {
            return new JsonResponse(['error' => 'Action not found'], 404);
        }

        // Check if the account is disabled
        $account = $action->getAccount();
        if ($account && !$account->isActive()) {
            return new JsonResponse(['error' => 'Cannot modify actions for a disabled account'], 403);
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
                    // Allow "No contacts available" as a valid value
                    if ($newValue !== "No contacts available") {
                        // Validate that the contact exists in the account's contacts list
                        $account = $action->getAccount();
                        if ($account) {
                            $contacts = $account->getContacts() ?? [];
                            if (!in_array($newValue, $contacts)) {
                                return new JsonResponse(['error' => 'Contact is not valid for this account'], 400);
                            }
                        }
                    }
                    $action->setContact($newValue);
                    break;
                case 'action':
                    $action->setTitle($newValue);
                    break;
                case 'owner':
                    // Handle null, empty, or invalid owner values
                    if (empty($newValue)) {
                        // If owner is required by database schema, return an error
                        return new JsonResponse(['error' => 'Owner is required'], 400);
                    } else {
                        $owner = $entityManager->getRepository(User::class)->find($newValue);
                        if (!$owner) {
                            return new JsonResponse(['error' => 'Owner not found'], 400);
                        }
                        $action->setOwner($owner);
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
}

<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Action;
use App\Entity\User;
use App\Form\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AccountController extends AbstractWebController
{
    #[Route('/accounts/{id}/edit', name: 'app_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AccountType::class, $account);
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
    #[Route('/api/users', name: 'app_get_users', methods: ['GET'])]
    public function getUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'name' => $user->getUsername()
            ];
        }

        return new JsonResponse($usersData);
    }
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
            case 'contact':
                if (strlen($value) >= 2 && strlen($value) <= 255) {
                    $account->setContact($value);
                } else {
                    return new JsonResponse(['error' => 'Contact must be between 2 and 255 characters'], 400);
                }
                break;
            case 'priority':
                if (in_array($value, ['Haute', 'Moyenne', 'Basse'])) {
                    $account->setPriority($value);
                } else {
                    return new JsonResponse(['error' => 'Priority must be one of: Haute, Moyenne, Basse'], 400);
                }
                break;
            case 'nextStep':
                $account->setNextStep($value);

                // If nextStep is updated, update the most recent action's nextStepDate
                if (!empty($value)) {
                    try {
                        // Try to parse the date from the nextStep value
                        $dateTime = new \DateTime($value);

                        // Find the most recent action for this account
                        $action = $entityManager->getRepository(Action::class)->findOneBy(
                            ['account' => $account],
                            ['createdAt' => 'DESC']
                        );

                        // If an action exists, update its nextStepDate
                        if ($action) {
                            $action->setNextStepDate($dateTime);
                        }
                    } catch (\Exception $e) {
                        // If date parsing fails, just continue without updating action
                    }
                }
                break;
            case 'owner':
                $owner = $entityManager->getRepository(User::class)->find($value);
                if ($owner) {
                    $account->setOwner($owner);
                } else {
                    return new JsonResponse(['error' => 'User not found'], 404);
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

        // Fetch the most recent action for each account
        $lastActions = [];

        if (!empty($accounts)) {
            // Get all account IDs
            $accountIds = array_map(function($account) {
                return $account->getId();
            }, $accounts);

            // Use Doctrine's query builder for better compatibility
            $qb = $entityManager->createQueryBuilder();
            $qb->select('a', 'acc', 'u')
               ->from(Action::class, 'a')
               ->join('a.account', 'acc')
               ->join('a.owner', 'u')
               ->where('acc.id IN (:accountIds)')
               ->setParameter('accountIds', $accountIds)
               ->orderBy('a.createdAt', 'DESC');

            $actions = $qb->getQuery()->getResult();

            // Group actions by account ID and keep only the most recent one
            $tempActions = [];
            foreach ($actions as $action) {
                $accountId = $action->getAccount()->getId();
                if (!isset($tempActions[$accountId])) {
                    $tempActions[$accountId] = $action;
                }
            }

            $lastActions = $tempActions;
        }

        // Create a new account instance for the form
        $account = new Account();

        // Create the form
        $form = $this->createFormBuilder($account)
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('contact', TextType::class, [
                'label' => 'Contact',
                'attr' => ['class' => 'form-control']
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => [
                    'High' => 'Haute',
                    'Medium' => 'Moyenne',
                    'Low' => 'Basse'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('nextStep', TextType::class, [
                'label' => 'Next Step',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Owner',
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
            function ($data) use ($entityManager) {
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
                'lastActions' => $lastActions
            ]
        );
    }

    #[Route('/accounts/{id}/actions', name: 'app_account_actions')]
    public function getAccountActions(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $account = $entityManager->getRepository(Account::class)->find($id);

        if (!$account) {
            return new JsonResponse(['error' => 'Account not found'], 404);
        }

        $actions = $entityManager->getRepository(Action::class)->findBy(
            ['account' => $account],
            ['createdAt' => 'DESC']
        );

        $actionsData = [];
        foreach ($actions as $action) {
            $actionsData[] = [
                'id' => $action->getId(),
                'title' => $action->getTitle(),
                'type' => $action->getType(),
                'nextStepDate' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null,
                'createdAt' => $action->getCreatedAt()->format('Y-m-d H:i:s'),
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown'
            ];
        }

        return new JsonResponse($actionsData);
    }

    #[Route('/accounts/create-ajax', name: 'app_create_account_ajax', methods: ['POST'])]
    public function createAccountAjax(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Get form data
        $name = $request->request->get('name');
        $contact = $request->request->get('contact');
        $priority = $request->request->get('priority');
        $nextStep = $request->request->get('nextStep');
        $ownerId = $request->request->get('owner');

        // Validate required fields
        if (!$name || !$contact || !$ownerId) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        // Get the owner
        $owner = $entityManager->getRepository(User::class)->find($ownerId);
        if (!$owner) {
            return new JsonResponse(['error' => 'Owner not found'], 404);
        }

        // Create a new account
        $account = new Account();
        $account->setName($name);
        $account->setContact($contact);
        $account->setPriority($priority);
        $account->setNextStep($nextStep);
        $account->setOwner($owner);

        // Save to database
        $entityManager->persist($account);
        $entityManager->flush();

        // Return the created account data
        return new JsonResponse([
            'id' => $account->getId(),
            'name' => $account->getName(),
            'contact' => $account->getContact(),
            'priority' => $account->getPriority(),
            'nextStep' => $account->getNextStep(),
            'owner' => $account->getOwner()->getUsername()
        ]);
    }

    #[Route('/accounts/{id}/create-action', name: 'app_create_account_action', methods: ['POST'])]
    public function createAction(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Find the account
        $account = $entityManager->getRepository(Account::class)->find($id);

        if (!$account) {
            return new JsonResponse(['error' => 'Account not found'], 404);
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

        // Create a new action
        $action = new Action();
        $action->setTitle($request->request->get('title'));
        $action->setType($request->request->get('type'));
        $action->setCreatedAt(new \DateTime()); // Explicitly set createdAt

        // Handle next step date
        $nextStepDate = $request->request->get('nextStepDate');
        if ($nextStepDate) {
            try {
                $dateTime = new \DateTime($nextStepDate);
                $action->setNextStepDate($dateTime);

                // Synchronize with account's nextStep field
                // Format the date as a string for the account's nextStep field
                $formattedDate = $dateTime->format('Y-m-d');
                $account->setNextStep($formattedDate);
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

            // Return the new action data
            return new JsonResponse([
                'id' => $action->getId(),
                'title' => $action->getTitle(),
                'type' => $action->getType(),
                'nextStepDate' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null,
                'createdAt' => $action->getCreatedAt()->format('Y-m-d H:i:s'),
                'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'Unknown'
            ]);
        } catch (\Exception $e) {
            // Log the detailed error
            error_log('Error creating action: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());

            // Return error response
            return new JsonResponse(['error' => 'Error creating action. Please try again.'], 400);
        }
    }
}

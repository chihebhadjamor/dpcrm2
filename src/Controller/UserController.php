<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Action;
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
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;

class UserController extends AbstractWebController
{
    #[Route('/users/create-ajax', name: 'app_create_user_ajax', methods: ['POST'])]
    public function createUserAjax(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Get form data
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // Validate required fields
        if (!$name || !$email || !$password) {
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

        // Create a new user
        $user = new User();
        $user->setName($name);
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
            'name' => $user->getName(),
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
                'name' => $user->getName()
            ];
        }

        return new JsonResponse($usersData);
    }

    #[Route('/users/{id}/actions', name: 'app_user_actions')]
    public function getUserActions(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $actions = $entityManager->getRepository(Action::class)->findBy(
            ['user' => $user],
            ['nextStepDate' => 'DESC']
        );

        $actionsData = [];
        foreach ($actions as $action) {
            $actionsData[] = [
                'id' => $action->getId(),
                'title' => $action->getTitle(),
                'type' => $action->getType(),
                'nextStepDate' => $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : null,
                'createdAt' => $action->getCreatedAt()->format('Y-m-d H:i:s'),
                'owner' => $action->getOwner()->getName()
            ];
        }

        return new JsonResponse($actionsData);
    }

    #[Route('/users/{id}/create-action', name: 'app_create_user_action', methods: ['POST'])]
    public function createAction(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Find the user
        $user = $entityManager->getRepository(User::class)->find($id);

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
        $action->setType($request->request->get('type'));

        // Handle next step date
        $nextStepDate = $request->request->get('nextStepDate');
        if ($nextStepDate) {
            $dateTime = new \DateTime($nextStepDate);
            $action->setNextStepDate($dateTime);
        }

        // Set relationships
        $action->setUser($user);
        $action->setOwner($owner);

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
            'owner' => $action->getOwner()->getName()
        ]);
    }

    #[Route('/users/{id}/update', name: 'app_update_user', methods: ['POST'])]
    public function updateUser(int $id, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Validate CSRF token
        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('update_user', $token))) {
            return $this->json(['error' => 'Invalid CSRF token'], 400);
        }

        // Find the user
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Update user fields
        $fieldName = $request->request->get('field');
        $value = $request->request->get('value');

        // Validate and update the field
        switch ($fieldName) {
            case 'name':
                if (strlen($value) >= 2 && strlen($value) <= 180) {
                    $user->setName($value);
                } else {
                    return $this->json(['error' => 'Name must be between 2 and 180 characters'], 400);
                }
                break;
            case 'email':
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    // Check if email is already in use by another user
                    $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $value]);
                    if ($existingUser && $existingUser->getId() !== $user->getId()) {
                        return $this->json(['error' => 'Email is already in use'], 400);
                    }
                    $user->setEmail($value);
                } else {
                    return $this->json(['error' => 'Invalid email format'], 400);
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
                    return $this->json(['error' => 'Value must be a boolean'], 400);
                }
                break;
            case 'secret_2fa':
                $user->setSecret2fa($value);
                break;
            default:
                return $this->json(['error' => 'Invalid field name'], 400);
        }

        // Save to database
        $entityManager->flush();

        // Return success response
        return $this->json([
            'success' => true,
            'message' => 'User updated successfully',
            'field' => $fieldName,
            'value' => $fieldName === 'roles' ? implode(', ', $user->getRoles()) : $value
        ]);
    }
}

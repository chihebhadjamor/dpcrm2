<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProfileController extends AbstractController
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Get the current user
        $user = $this->getUser();

        // If no user is logged in, redirect to login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Create password change form
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        // Handle password form submission
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();

            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_profile');
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
                $this->addFlash('success', 'Your password has been updated successfully. A notification email has been sent.');
            } else {
                $this->addFlash('warning', 'Your password has been updated successfully, but the notification email could not be sent.');
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'passwordForm' => $passwordForm->createView(),
        ]);
    }
}

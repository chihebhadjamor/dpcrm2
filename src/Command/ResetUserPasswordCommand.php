<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:reset-user-password',
    description: 'Securely reset a user\'s password',
)]
class ResetUserPasswordCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Reset User Password');

        // Get username or email
        $identifier = $io->ask('Enter username or email of the user');

        // Find the user by username or email
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['username' => $identifier]);

        if (!$user) {
            $user = $userRepository->findOneBy(['email' => $identifier]);
        }

        // Check if user exists
        if (!$user) {
            $io->error(sprintf("Error: User '%s' not found.", $identifier));
            return Command::FAILURE;
        }

        // Check if user is disabled
        if ($user->isDisabled()) {
            $io->warning(sprintf("Warning: User '%s' is disabled and their password cannot be changed.", $user->getUsername()));
            return Command::FAILURE;
        }

        // Get new password (hidden input)
        $passwordQuestion = new Question('Enter new password');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setValidator(function ($password) {
            if (empty($password)) {
                throw new \RuntimeException('Password cannot be empty.');
            }
            return $password;
        });
        $password = $io->askQuestion($passwordQuestion);

        // Confirm password
        $confirmPasswordQuestion = new Question('Confirm new password');
        $confirmPasswordQuestion->setHidden(true);
        $confirmPassword = $io->askQuestion($confirmPasswordQuestion);

        // Check if passwords match
        if ($password !== $confirmPassword) {
            $io->error('Error: Passwords do not match.');
            return Command::FAILURE;
        }

        try {
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Save to database
            $this->entityManager->flush();

            $io->success(sprintf("The password for user '%s' has been successfully updated.", $user->getUsername()));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while resetting the password: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}

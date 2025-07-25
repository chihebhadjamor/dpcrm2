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
    name: 'app:create-admin',
    description: 'Creates a new user with administrative privileges',
)]
class CreateAdminCommand extends Command
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
        $io->title('Create Admin User');

        // Get username
        $username = $io->ask('Enter username', null, function ($username) {
            if (empty($username)) {
                throw new \RuntimeException('Username cannot be empty.');
            }

            // Check if username already exists
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUser) {
                throw new \RuntimeException('Username already exists.');
            }

            return $username;
        });

        // Get email
        $email = $io->ask('Enter email', null, function ($email) {
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Please enter a valid email address.');
            }

            // Check if email already exists
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                throw new \RuntimeException('Email already exists.');
            }

            return $email;
        });

        // Get password (hidden input)
        $passwordQuestion = new Question('Enter password');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setValidator(function ($password) {
            if (empty($password)) {
                throw new \RuntimeException('Password cannot be empty.');
            }
            return $password;
        });
        $password = $io->askQuestion($passwordQuestion);

        try {
            // Create new user
            $user = new User();
            $user->setUsername($username);
            $user->setEmail($email);

            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Set admin role
            $user->setRoles(['ROLE_ADMIN']);

            // Save to database
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success(sprintf('Successfully created admin user: %s', $username));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while creating the admin user: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}

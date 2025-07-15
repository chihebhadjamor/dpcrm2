<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:update-user-password',
    description: 'Updates a user password in the database',
)]
class UpdateUserPasswordCommand extends Command
{
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        #[Autowire('%app.project_dir%')] string $projectDir
    ) {
        parent::__construct();
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = 'chiheb.hadjamor@datapowa.fr';
        $plainPassword = 'DataPowa123!';
        $roles = ['ROLE_ADMIN', 'ROLE_USER'];

        // Check if user exists
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['name' => $name]);

        if (!$user) {
            // Create a new user
            $user = new User();
            $user->setName($name);
            $user->setRoles($roles);
        }

        // Update password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Save to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(sprintf('Password for user %s has been updated to %s', $name, $plainPassword));

        return Command::SUCCESS;
    }
}

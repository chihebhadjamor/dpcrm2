<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:update-user-role',
    description: 'Updates a user role in the database',
)]
class UpdateUserRoleCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        #[Autowire('%app.project_dir%')] string $projectDir
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = 'chiheb';
        $roles = ['ROLE_USER'];

        // Check if user exists
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            $output->writeln(sprintf('User %s not found', $username));
            return Command::FAILURE;
        }

        // Update roles
        $user->setRoles($roles);

        // Save to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(sprintf('Roles for user %s have been updated to %s', $username, implode(', ', $roles)));

        return Command::SUCCESS;
    }
}

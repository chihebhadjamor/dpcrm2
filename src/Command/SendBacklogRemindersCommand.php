<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Action;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-backlog-reminders',
    description: 'Send formatted backlog status emails to all active users',
)]
class SendBacklogRemindersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private EmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->emailService = $emailService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Sending Backlog Reminder Emails to Active Users');

        // Fetch all active users
        $userRepository = $this->entityManager->getRepository(User::class);
        $activeUsers = $userRepository->findBy(['disabled' => false]);

        if (empty($activeUsers)) {
            $io->warning('No active users found in the system.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d active users.', count($activeUsers)));
        $io->progressStart(count($activeUsers));

        $successCount = 0;
        $errorCount = 0;

        // Process each active user
        foreach ($activeUsers as $user) {
            try {
                // Get user's open actions
                $openActions = $this->getOpenActionsForUser($user);

                // Send appropriate email based on whether they have open actions or not
                $this->emailService->sendBacklogReminderEmail(
                    $user->getEmail(),
                    $user->getUsername(),
                    $openActions
                );

                $actionCount = count($openActions);
                $io->progressAdvance();
                $successCount++;

                // Output detailed message (not visible during progress bar)
                $io->writeln(
                    sprintf(
                        ' <info>✓</info> Sent email to %s (%s) with %d open action(s)',
                        $user->getUsername(),
                        $user->getEmail(),
                        $actionCount
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );
            } catch (\Exception $e) {
                $errorCount++;
                $io->progressAdvance();

                // Output error message (not visible during progress bar)
                $io->writeln(
                    sprintf(
                        ' <error>✗</error> Failed to send email to %s (%s): %s',
                        $user->getUsername(),
                        $user->getEmail(),
                        $e->getMessage()
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }
        }

        $io->progressFinish();

        // Summary message
        if ($errorCount === 0) {
            $io->success(sprintf('Successfully processed and sent status emails to all %d active users.', $successCount));
        } else {
            $io->warning(sprintf(
                'Processed %d users: %d successful, %d failed. Check logs for details.',
                count($activeUsers),
                $successCount,
                $errorCount
            ));
        }

        return Command::SUCCESS;
    }

    /**
     * Get open actions for a specific user
     */
    private function getOpenActionsForUser(User $user): array
    {
        $openActions = [];

        // Get all actions for the user
        $actions = $user->getActions();

        // Filter to only include open actions
        foreach ($actions as $action) {
            if (!$action->isClosed()) {
                $openActions[] = $action;
            }
        }

        return $openActions;
    }
}

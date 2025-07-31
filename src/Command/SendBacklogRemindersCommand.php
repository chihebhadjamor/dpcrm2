<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Action;
use App\Entity\CronLog;
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

        // Create a buffer to capture detailed output
        $outputBuffer = '';
        $outputBuffer .= "Command: app:send-backlog-reminders\n";
        $outputBuffer .= "Started at: " . (new \DateTime())->format('Y-m-d H:i:s') . "\n\n";

        // Create a log entry for this command execution
        $cronLog = new CronLog();
        $cronLog->setCommand('app:send-backlog-reminders');
        $cronLog->setStatus(CronLog::STATUS_SUCCESS); // Default to success, will update if there's an error
        $this->entityManager->persist($cronLog);

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

                // Add to output buffer
                $successMsg = sprintf('✓ Sent email to %s (%s) with %d open action(s)',
                    $user->getUsername(),
                    $user->getEmail(),
                    $actionCount
                );
                $outputBuffer .= $successMsg . "\n";

                // Output detailed message (not visible during progress bar)
                $io->writeln(
                    ' <info>' . $successMsg . '</info>',
                    OutputInterface::VERBOSITY_VERBOSE
                );
            } catch (\Exception $e) {
                $errorCount++;
                $io->progressAdvance();

                // Add detailed error to output buffer including stack trace
                $errorMsg = sprintf('✗ Failed to send email to %s (%s): %s',
                    $user->getUsername(),
                    $user->getEmail(),
                    $e->getMessage()
                );
                $outputBuffer .= $errorMsg . "\n";
                $outputBuffer .= "Exception details:\n";
                $outputBuffer .= $e->getTraceAsString() . "\n\n";

                // Output error message (not visible during progress bar)
                $io->writeln(
                    ' <error>' . $errorMsg . '</error>',
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }
        }

        $io->progressFinish();

        // Summary message
        if ($errorCount === 0) {
            $successMessage = sprintf('Successfully processed and sent status emails to all %d active users.', $successCount);
            $io->success($successMessage);

            // Add summary to output buffer
            $outputBuffer .= "\nSUMMARY: " . $successMessage;

            // Update log entry with success message
            $cronLog->setMessage($successMessage);
        } else {
            $warningMessage = sprintf(
                'Processed %d users: %d successful, %d failed.',
                count($activeUsers),
                $successCount,
                $errorCount
            );
            $io->warning($warningMessage);

            // Add summary to output buffer
            $outputBuffer .= "\nSUMMARY: " . $warningMessage;

            // Update log entry with warning message and failure status
            $cronLog->setStatus(CronLog::STATUS_FAILURE);
            $cronLog->setMessage($warningMessage);
        }

        // Add completion timestamp to output buffer
        $outputBuffer .= "\nCompleted at: " . (new \DateTime())->format('Y-m-d H:i:s');

        // Set the detailed output in the log entry
        $cronLog->setOutput($outputBuffer);

        // Save the log entry
        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    /**
     * Get open actions for a specific user, sorted by Action Date in ascending order
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

        // Sort open actions by NextStepDate in ascending order
        usort($openActions, function($a, $b) {
            // Handle null dates (place them at the end)
            if ($a->getNextStepDate() === null) {
                return 1;
            }
            if ($b->getNextStepDate() === null) {
                return -1;
            }

            // Compare dates (earliest first)
            return $a->getNextStepDate() <=> $b->getNextStepDate();
        });

        return $openActions;
    }
}

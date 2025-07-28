<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Entity\Account;
use App\Entity\Action;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Create a kernel instance
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

// Get the entity manager
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

// Get all accounts
$accounts = $entityManager->getRepository(Account::class)->findAll();

echo "Total accounts: " . count($accounts) . "\n";

// Count active and inactive accounts
$activeAccounts = 0;
$inactiveAccounts = 0;

foreach ($accounts as $account) {
    if ($account->getStatus()) {
        $activeAccounts++;
    } else {
        $inactiveAccounts++;
    }
}

echo "Active accounts: " . $activeAccounts . "\n";
echo "Inactive accounts: " . $inactiveAccounts . "\n";

// Get all actions
$actions = $entityManager->getRepository(Action::class)->findAll();

echo "Total actions: " . count($actions) . "\n";

// Count open and closed actions
$openActions = 0;
$closedActions = 0;

foreach ($actions as $action) {
    if ($action->isClosed()) {
        $closedActions++;
    } else {
        $openActions++;
    }
}

echo "Open actions: " . $openActions . "\n";
echo "Closed actions: " . $closedActions . "\n";

// Count actions with next step date in the past, within 7 days, and more than 7 days in the future
$pastActions = 0;
$upcomingActions = 0;
$futureActions = 0;
$noDateActions = 0;

$today = new \DateTime();
$today->setTime(0, 0, 0);

$sevenDaysLater = clone $today;
$sevenDaysLater->modify('+7 days');

foreach ($actions as $action) {
    $nextStepDate = $action->getNextStepDate();

    if (!$nextStepDate) {
        $noDateActions++;
    } elseif ($nextStepDate < $today) {
        $pastActions++;
    } elseif ($nextStepDate <= $sevenDaysLater) {
        $upcomingActions++;
    } else {
        $futureActions++;
    }
}

echo "Actions with next step date in the past: " . $pastActions . "\n";
echo "Actions with next step date within 7 days: " . $upcomingActions . "\n";
echo "Actions with next step date more than 7 days in the future: " . $futureActions . "\n";
echo "Actions with no next step date: " . $noDateActions . "\n";

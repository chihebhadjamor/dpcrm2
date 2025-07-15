<?php
// Test script to simulate action creation

// Include Symfony's bootstrap file
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use App\Entity\Action;
use App\Entity\Account;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

return function (KernelInterface $kernel) {
    $container = $kernel->getContainer();
    $entityManager = $container->get(EntityManagerInterface::class);

    // Find an account to use for testing
    $account = $entityManager->getRepository(Account::class)->findOneBy([]);

    if (!$account) {
        echo "No account found for testing. Please create an account first.";
        return new Response("No account found", 404);
    }

    // Find a user to use as owner
    $owner = $entityManager->getRepository(User::class)->findOneBy(['name' => 'chiheb']);

    if (!$owner) {
        echo "User 'chiheb' not found. Using first available user.";
        $owner = $entityManager->getRepository(User::class)->findOneBy([]);

        if (!$owner) {
            echo "No users found for testing. Please create a user first.";
            return new Response("No users found", 404);
        }
    }

    // Create a new action
    $action = new Action();
    $action->setTitle("Action1 for ERAMET");
    $action->setType("Email");

    // Set next step date to today
    $action->setNextStepDate(new \DateTime());

    // Set relationships
    $action->setAccount($account);
    $action->setOwner($owner);

    try {
        // Save to database
        $entityManager->persist($action);
        $entityManager->flush();

        echo "Action created successfully with ID: " . $action->getId();
        echo "<br>Title: " . $action->getTitle();
        echo "<br>Type: " . $action->getType();
        echo "<br>Next Step Date: " . $action->getNextStepDate()->format('Y-m-d H:i:s');
        echo "<br>Owner: " . $action->getOwner()->getName();

        return new Response("Action created successfully", 200);
    } catch (\Exception $e) {
        echo "Error creating action: " . $e->getMessage();
        return new Response("Error: " . $e->getMessage(), 400);
    }
};

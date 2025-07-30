<?php

namespace App\Service;

use App\Entity\Action;
use App\Entity\ActionHistory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActionHistoryService
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * Create a history entry for an action only if there are actual changes
     */
    public function createHistoryEntry(Action $action): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('The current user must be an instance of User');
        }

        // Get the most recent history entry for this action
        $lastHistory = $this->getLastHistoryEntry($action);

        // If there's no previous history or there are actual changes, create a new entry
        if (!$lastHistory || $this->hasChanges($action, $lastHistory)) {
            $history = new ActionHistory();
            $history->setAction($action);
            $history->setTitle($action->getTitle());
            $history->setActionDate($action->getNextStepDate());
            // Handle potentially null owner
            $history->setOwner($action->getOwner());
            $history->setClosed($action->isClosed());
            $history->setContact($action->getContact());
            $history->setUpdatedBy($user);

            // Add the history to the action's collection
            $action->addActionHistory($history);

            $this->entityManager->persist($history);
            // Don't flush here, let the controller handle it
        }
    }

    /**
     * Get the most recent history entry for an action
     */
    private function getLastHistoryEntry(Action $action): ?ActionHistory
    {
        $histories = $action->getActionHistories();
        if ($histories->isEmpty()) {
            return null;
        }

        // Sort histories by updatedAt in descending order
        $iterator = $histories->getIterator();
        $iterator->uasort(function (ActionHistory $a, ActionHistory $b) {
            return $b->getUpdatedAt() <=> $a->getUpdatedAt();
        });

        // Return the first (most recent) history entry
        return $iterator->current();
    }

    /**
     * Check if there are changes between the current action state and the last history entry
     */
    private function hasChanges(Action $action, ActionHistory $lastHistory): bool
    {
        // Compare tracked fields: Title, Action Date, Owner, Contact, Status (closed)
        if ($action->getTitle() !== $lastHistory->getTitle()) {
            return true;
        }

        // Compare dates - need special handling for null values and date comparison
        $actionDate = $action->getNextStepDate();
        $historyDate = $lastHistory->getActionDate();

        if (($actionDate === null && $historyDate !== null) ||
            ($actionDate !== null && $historyDate === null)) {
            return true;
        }

        if ($actionDate !== null && $historyDate !== null &&
            $actionDate->format('Y-m-d H:i:s') !== $historyDate->format('Y-m-d H:i:s')) {
            return true;
        }

        // Compare owners - need special handling for null values
        $actionOwner = $action->getOwner();
        $historyOwner = $lastHistory->getOwner();

        if (($actionOwner === null && $historyOwner !== null) ||
            ($actionOwner !== null && $historyOwner === null)) {
            return true;
        }

        if ($actionOwner !== null && $historyOwner !== null &&
            $actionOwner->getId() !== $historyOwner->getId()) {
            return true;
        }

        // Compare contact
        if ($action->getContact() !== $lastHistory->getContact()) {
            return true;
        }

        // Compare closed status
        if ($action->isClosed() !== $lastHistory->isClosed()) {
            return true;
        }

        // No changes detected
        return false;
    }
}

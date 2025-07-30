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
     * Create a history entry for an action
     */
    public function createHistoryEntry(Action $action): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('The current user must be an instance of User');
        }

        $history = new ActionHistory();
        $history->setAction($action);
        $history->setTitle($action->getTitle());
        $history->setActionDate($action->getNextStepDate());
        // Handle potentially null owner
        $history->setOwner($action->getOwner());
        $history->setClosed($action->isClosed());
        $history->setUpdatedBy($user);

        // Add the history to the action's collection
        $action->addActionHistory($history);

        $this->entityManager->persist($history);
        // Don't flush here, let the controller handle it
    }
}

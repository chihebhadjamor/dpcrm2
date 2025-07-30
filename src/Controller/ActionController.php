<?php

namespace App\Controller;

use App\Entity\Action;
use App\Entity\ActionHistory;
use App\Entity\History;
use App\Service\AppSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActionController extends AbstractController
{
    private AppSettingsService $appSettingsService;

    public function __construct(AppSettingsService $appSettingsService)
    {
        $this->appSettingsService = $appSettingsService;
    }

    #[Route('/actions/{id}/history', name: 'app_action_history', methods: ['GET'])]
    public function getActionHistory(int $id, EntityManagerInterface $entityManager): Response
    {
        // Ensure the user is authenticated
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $action = $entityManager->getRepository(Action::class)->find($id);

            if (!$action) {
                return new JsonResponse(['error' => 'Action not found'], Response::HTTP_NOT_FOUND);
            }

            // Get action histories from the action entity
            $actionHistories = $action->getActionHistories()->toArray();

            // Sort by updatedAt in descending order (most recent first)
            usort($actionHistories, function(ActionHistory $a, ActionHistory $b) {
                return $b->getUpdatedAt() <=> $a->getUpdatedAt();
            });

            // Get regular histories
            $histories = $action->getHistories()->toArray();

            // Sort by createdAt in descending order (most recent first)
            usort($histories, function(History $a, History $b) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            });

            // Combine both types of histories
            $allHistories = array_merge($actionHistories, $histories);

            $historyData = [];
            $previousEntry = null;

            foreach ($allHistories as $history) {
                $currentEntry = [];

                if ($history instanceof ActionHistory) {
                    $currentEntry = [
                        'title' => $history->getTitle(),
                        'actionDate' => $history->getActionDate() ? $this->appSettingsService->formatDate($history->getActionDate()) : null,
                        'owner' => $history->getOwner() ? $history->getOwner()->getUsername() : 'N/A',
                        'contact' => $history->getContact() ?: 'N/A',
                        'status' => $history->isClosed() ? 'Closed' : 'Open',
                        'updatedBy' => $history->getUpdatedBy() ? $history->getUpdatedBy()->getUsername() : 'System',
                        'updatedAt' => $history->getUpdatedAt() ? $this->appSettingsService->formatDateTime($history->getUpdatedAt()) : 'N/A',
                    ];
                } elseif ($history instanceof History) {
                    $currentEntry = [
                        'title' => $action->getTitle() . ' - ' . $history->getNote(), // Include note in title
                        'actionDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
                        'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'N/A',
                        'contact' => $action->getContact() ?: 'N/A',
                        'status' => $action->isClosed() ? 'Closed' : 'Open',
                        'updatedBy' => $history->getAuthor() ? $history->getAuthor()->getUsername() : 'System',
                        'updatedAt' => $history->getCreatedAt() ? $this->appSettingsService->formatDateTime($history->getCreatedAt()) : 'N/A',
                    ];
                }

                // Identify changed fields by comparing with previous entry
                $changedFields = [];
                if ($previousEntry !== null) {
                    foreach ($currentEntry as $field => $value) {
                        // Skip updatedBy and updatedAt fields from comparison
                        if ($field !== 'updatedBy' && $field !== 'updatedAt') {
                            if ($previousEntry[$field] !== $value) {
                                $changedFields[] = $field;
                            }
                        }
                    }
                }

                $currentEntry['changedFields'] = $changedFields;
                $historyData[] = $currentEntry;
                $previousEntry = $currentEntry;
            }

            // If no history entries exist yet, add the current action state as the initial history
            if (empty($allHistories)) {
                $historyData[] = [
                    'title' => $action->getTitle(),
                    'actionDate' => $action->getNextStepDate() ? $this->appSettingsService->formatDate($action->getNextStepDate()) : null,
                    'owner' => $action->getOwner() ? $action->getOwner()->getUsername() : 'N/A',
                    'contact' => $action->getContact() ?: 'N/A',
                    'status' => $action->isClosed() ? 'Closed' : 'Open',
                    'updatedBy' => 'System',
                    'updatedAt' => $action->getCreatedAt() ? $this->appSettingsService->formatDateTime($action->getCreatedAt()) : 'N/A',
                    'changedFields' => [], // Initial entry has no changed fields
                ];
            }

            // Check if the request expects JSON
            if ($this->isRequestExpectingJson()) {
                // Return JSON response with action and history data
                return new JsonResponse([
                    'action' => [
                        'id' => $action->getId(),
                        'title' => $action->getTitle()
                    ],
                    'histories' => $historyData
                ]);
            } else {
                // Render the template for HTML response
                return $this->render('action/history_modal.html.twig', [
                    'action' => [
                        'id' => $action->getId(),
                        'title' => $action->getTitle()
                    ],
                    'histories' => $historyData
                ]);
            }
        } catch (\Exception $e) {
            if ($this->isRequestExpectingJson()) {
                return new JsonResponse(['error' => 'An error occurred while fetching action history: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                throw $e; // Let Symfony handle the exception for HTML requests
            }
        }
    }

    /**
     * Check if the request is expecting a JSON response
     */
    private function isRequestExpectingJson(): bool
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if (!$request) {
            return false;
        }

        // Check Accept header
        $acceptHeader = $request->headers->get('Accept');
        if ($acceptHeader && strpos($acceptHeader, 'application/json') !== false) {
            return true;
        }

        // Check if it's an AJAX request
        if ($request->isXmlHttpRequest()) {
            return true;
        }

        // Check for JSON format in query parameters
        if ($request->query->get('_format') === 'json') {
            return true;
        }

        return false;
    }
}

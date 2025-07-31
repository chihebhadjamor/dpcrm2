<?php

namespace App\Controller;

use App\Entity\CronLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AdminController extends AbstractWebController
{
    /**
     * Check if the current user has admin access
     * Throws AccessDeniedException if not
     */
    private function denyAccessUnlessAdmin(): void
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You do not have permission to access this page.');
        }
    }

    #[Route('/admin/cron-logs', name: 'app_admin_cron_logs')]
    public function cronLogs(EntityManagerInterface $entityManager): Response
    {
        // Only allow administrators to access this page
        $this->denyAccessUnlessAdmin();

        // Get all cron logs ordered by execution date (newest first)
        $cronLogs = $entityManager->getRepository(CronLog::class)->findAllOrderedByDate();

        // Return the view with cron logs
        return $this->render('admin/cron_logs.html.twig', [
            'cronLogs' => $cronLogs
        ]);
    }
}

<?php

namespace App\Twig;

use App\Service\AppSettingsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private AppSettingsService $appSettingsService;

    public function __construct(AppSettingsService $appSettingsService)
    {
        $this->appSettingsService = $appSettingsService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('app_date', [$this, 'formatDate']),
            new TwigFilter('app_datetime', [$this, 'formatDateTime']),
        ];
    }

    public function formatDate($date): ?string
    {
        if ($date === null) {
            return null;
        }

        if (is_string($date)) {
            try {
                $date = new \DateTime($date);
            } catch (\Exception $e) {
                return $date;
            }
        }

        return $this->appSettingsService->formatDate($date);
    }

    public function formatDateTime($datetime): ?string
    {
        if ($datetime === null) {
            return null;
        }

        if (is_string($datetime)) {
            try {
                $datetime = new \DateTime($datetime);
            } catch (\Exception $e) {
                return $datetime;
            }
        }

        return $this->appSettingsService->formatDateTime($datetime);
    }
}

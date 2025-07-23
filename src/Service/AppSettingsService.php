<?php

namespace App\Service;

use App\Entity\AppSettings;
use Doctrine\ORM\EntityManagerInterface;

class AppSettingsService
{
    private EntityManagerInterface $entityManager;
    private ?string $dateFormat = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get the application-wide date format
     *
     * @return string The date format (e.g., 'Y-m-d')
     */
    public function getDateFormat(): string
    {
        if ($this->dateFormat === null) {
            $this->loadDateFormat();
        }

        return $this->dateFormat;
    }

    /**
     * Format a date using the application-wide date format
     *
     * @param \DateTimeInterface|null $date The date to format
     * @return string|null The formatted date or null if the date is null
     */
    public function formatDate(?\DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->format($this->getDateFormat());
    }

    /**
     * Format a datetime using the application-wide date format plus time
     *
     * @param \DateTimeInterface|null $datetime The datetime to format
     * @return string|null The formatted datetime or null if the datetime is null
     */
    public function formatDateTime(?\DateTimeInterface $datetime): ?string
    {
        if ($datetime === null) {
            return null;
        }

        return $datetime->format($this->getDateFormat() . ' H:i:s');
    }

    /**
     * Load the date format from the database
     */
    private function loadDateFormat(): void
    {
        try {
            $setting = $this->entityManager->getRepository(AppSettings::class)
                ->findOneBy(['setting_name' => 'date_format']);

            $this->dateFormat = $setting ? $setting->getSettingValue() : 'Y-m-d';
        } catch (\Exception $e) {
            // If there's any error (e.g., database connection issue or table doesn't exist yet),
            // fall back to the default format
            $this->dateFormat = 'Y-m-d';
        }
    }
}

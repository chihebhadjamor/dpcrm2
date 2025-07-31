<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CronLogRepository;

#[ORM\Entity(repositoryClass: CronLogRepository::class)]
#[ORM\Table(name: 'cron_log')]
#[ORM\HasLifecycleCallbacks]
class CronLog
{
    public const STATUS_SUCCESS = 'Success';
    public const STATUS_FAILURE = 'Failure';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $command = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $executedAt = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    public function __construct()
    {
        $this->executedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(string $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getExecutedAt(): ?\DateTimeInterface
    {
        return $this->executedAt;
    }

    public function setExecutedAt(\DateTimeInterface $executedAt): static
    {
        $this->executedAt = $executedAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailure(): bool
    {
        return $this->status === self::STATUS_FAILURE;
    }
}

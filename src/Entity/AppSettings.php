<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`app_settings`')]
class AppSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $setting_name;

    #[ORM\Column(length: 255)]
    private string $setting_value;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingName(): string
    {
        return $this->setting_name;
    }

    public function setSettingName(string $setting_name): static
    {
        $this->setting_name = $setting_name;

        return $this;
    }

    public function getSettingValue(): string
    {
        return $this->setting_value;
    }

    public function setSettingValue(string $setting_value): static
    {
        $this->setting_value = $setting_value;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}

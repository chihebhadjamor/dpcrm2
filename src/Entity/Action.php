<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'action')]
#[ORM\Index(columns: ['next_step_date'], name: 'idx_action_next_step_date')]
#[ORM\HasLifecycleCallbacks]
class Action
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['Appel', 'Email', 'RDV'])]
    private ?string $type = null;


    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $nextStepDate = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Account $account = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(name: 'date_closed', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateClosed = null;

    #[ORM\Column(name: 'closed', type: 'boolean', options: ['default' => false])]
    private bool $closed = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\OneToMany(mappedBy: 'action', targetEntity: History::class, cascade: ['persist', 'remove'])]
    private Collection $histories;


    public function __construct()
    {
        $this->histories = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }


    public function getNextStepDate(): ?\DateTimeInterface
    {
        return $this->nextStepDate;
    }

    public function setNextStepDate(?\DateTimeInterface $nextStepDate): static
    {
        $this->nextStepDate = $nextStepDate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, History>
     */
    public function getHistories(): Collection
    {
        return $this->histories;
    }

    public function addHistory(History $history): static
    {
        if (!$this->histories->contains($history)) {
            $this->histories->add($history);
            $history->setAction($this);
        }

        return $this;
    }

    public function removeHistory(History $history): static
    {
        if ($this->histories->removeElement($history)) {
            // set the owning side to null (unless already changed)
            if ($history->getAction() === $this) {
                $history->setAction(null);
            }
        }

        return $this;
    }

    public function getDateClosed(): ?\DateTimeInterface
    {
        return $this->dateClosed;
    }

    public function setDateClosed(?\DateTimeInterface $dateClosed): static
    {
        $this->dateClosed = $dateClosed;

        return $this;
    }

    public function isClosed(): bool
    {
        // For backward compatibility, check both properties
        // If dateClosed is set but closed is false, update closed
        if ($this->dateClosed !== null && !$this->closed) {
            $this->closed = true;
        }
        return $this->closed;
    }

    public function close(): static
    {
        $this->dateClosed = new \DateTime();
        $this->closed = true;

        return $this;
    }

    public function reopen(): static
    {
        $this->dateClosed = null;
        $this->closed = false;

        return $this;
    }

    public function getClosed(): bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): static
    {
        $this->closed = $closed;

        if ($closed && $this->dateClosed === null) {
            $this->dateClosed = new \DateTime();
        } elseif (!$closed) {
            $this->dateClosed = null;
        }

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }
}

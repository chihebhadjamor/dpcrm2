<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ArrayAccess;

#[ORM\Entity]
#[ORM\Table(name: 'account')]
#[ORM\Index(columns: ['name'], name: 'idx_account_name')]
#[ORM\HasLifecycleCallbacks]
class Account implements ArrayAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $name = null;



    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $website = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['Active', 'Inactive', 'Pending'])]
    private ?string $status = 'Active';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $context = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $contacts = [];

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Action::class, cascade: ['persist', 'remove'])]
    private Collection $actions;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;


    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }





    /**
     * @return Collection<int, Action>
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(Action $action): static
    {
        if (!$this->actions->contains($action)) {
            $this->actions->add($action);
            $action->setAccount($this);
        }

        return $this;
    }

    public function removeAction(Action $action): static
    {
        if ($this->actions->removeElement($action)) {
            // set the owning side to null (unless already changed)
            if ($action->getAccount() === $this) {
                $action->setAccount(null);
            }
        }

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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

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

    public static function getAvailableStatuses(): array
    {
        return [
            'Active' => 'Active',
            'Inactive' => 'Inactive',
            'Pending' => 'Pending'
        ];
    }


    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getContacts(): ?array
    {
        return $this->contacts;
    }

    /**
     * @param array|null $contacts
     * @return static
     */
    public function setContacts(?array $contacts): static
    {
        $this->contacts = $contacts;

        return $this;
    }

    /**
     * Add a contact to the contacts array
     *
     * @param string $contact
     * @return static
     */
    public function addContact(string $contact): static
    {
        if (!$this->contacts) {
            $this->contacts = [];
        }

        if (!in_array($contact, $this->contacts)) {
            $this->contacts[] = $contact;
        }

        return $this;
    }

    /**
     * Remove a contact from the contacts array
     *
     * @param string $contact
     * @return static
     */
    public function removeContact(string $contact): static
    {
        if ($this->contacts) {
            $key = array_search($contact, $this->contacts);
            if ($key !== false) {
                unset($this->contacts[$key]);
                // Reindex the array
                $this->contacts = array_values($this->contacts);
            }
        }

        return $this;
    }

    /**
     * Determines whether an offset exists
     */
    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset) || method_exists($this, 'get' . ucfirst($offset));
    }

    /**
     * Gets the value at the specified offset
     */
    public function offsetGet(mixed $offset): mixed
    {
        $getter = 'get' . ucfirst($offset);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (property_exists($this, $offset)) {
            return $this->$offset;
        }

        return null;
    }

    /**
     * Sets the value at the specified offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $setter = 'set' . ucfirst($offset);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    /**
     * Unsets an offset
     */
    public function offsetUnset(mixed $offset): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = null;
        }
    }
}

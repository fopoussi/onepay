<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\OnepayUserController;
use App\Repository\OnepayUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: OnepayUserRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Delete(),
        new Patch(),
        new Post(
            name: 'login',
            uriTemplate: '/login',
            controller: OnepayUserController::class . '::login'
        ),
        new Post(
            name: 'logout',
            uriTemplate: '/logout',
            controller: OnepayUserController::class . '::logout'
        ),
        new Get(
            name: 'profile',
            uriTemplate: '/user/profile',
            controller: OnepayUserController::class . '::profile'
        )
    ]
)]
class OnepayUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $phone_number = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(nullable: true)]
    private ?bool $two_factor_enabled = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(nullable: true)]
    private ?float $balance = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $update_at = null;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    /**
     * @var Collection<int, OnepayTransaction>
     */
    #[ORM\OneToMany(targetEntity: OnepayTransaction::class, mappedBy: 'user')]
    private Collection $transactions;

    /**
     * @var Collection<int, OnepayNotification>
     */
    #[ORM\OneToMany(targetEntity: OnepayNotification::class, mappedBy: 'user')]
    private Collection $notifications;

    /**
     * @var Collection<int, OnepayLoginHistory>
     */
    #[ORM\OneToMany(targetEntity: OnepayLoginHistory::class, mappedBy: 'user')]
    private Collection $loginHistories;

    /**
     * @var Collection<int, PhoneNumber>
     */
    #[ORM\OneToMany(targetEntity: PhoneNumber::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $phoneNumbers;

    /**
     * @var Collection<int, MobileMoneyAccount>
     */
    #[ORM\OneToMany(targetEntity: MobileMoneyAccount::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $mobileAccounts;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->loginHistories = new ArrayCollection();
        $this->phoneNumbers = new ArrayCollection();
        $this->mobileAccounts = new ArrayCollection();
        $this->created_at = new \DateTime();
        $this->update_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): static
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function isTwoFactorEnabled(): ?bool
    {
        return $this->two_factor_enabled;
    }

    public function setTwoFactorEnabled(?bool $two_factor_enabled): static
    {
        $this->two_factor_enabled = $two_factor_enabled;
        return $this;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(?float $balance): static
    {
        $this->balance = $balance;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->update_at;
    }

    public function setUpdateAt(?\DateTimeInterface $update_at): static
    {
        $this->update_at = $update_at;
        return $this;
    }

    /**
     * @return Collection<int, OnepayTransaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(OnepayTransaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setUser($this);
        }
        return $this;
    }

    public function removeTransaction(OnepayTransaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            if ($transaction->getUser() === $this) {
                $transaction->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, OnepayNotification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(OnepayNotification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }
        return $this;
    }

    public function removeNotification(OnepayNotification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, OnepayLoginHistory>
     */
    public function getLoginHistories(): Collection
    {
        return $this->loginHistories;
    }

    public function addLoginHistory(OnepayLoginHistory $loginHistory): static
    {
        if (!$this->loginHistories->contains($loginHistory)) {
            $this->loginHistories->add($loginHistory);
            $loginHistory->setUser($this);
        }
        return $this;
    }

    public function removeLoginHistory(OnepayLoginHistory $loginHistory): static
    {
        if ($this->loginHistories->removeElement($loginHistory)) {
            if ($loginHistory->getUser() === $this) {
                $loginHistory->setUser(null);
            }
        }
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Implement the logic to erase credentials if needed
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return Collection<int, PhoneNumber>
     */
    public function getPhoneNumbers(): Collection
    {
        return $this->phoneNumbers;
    }

    public function addPhoneNumber(PhoneNumber $phoneNumber): static
    {
        if (!$this->phoneNumbers->contains($phoneNumber)) {
            $this->phoneNumbers->add($phoneNumber);
            $phoneNumber->setUser($this);
        }
        return $this;
    }

    public function removePhoneNumber(PhoneNumber $phoneNumber): static
    {
        if ($this->phoneNumbers->removeElement($phoneNumber)) {
            if ($phoneNumber->getUser() === $this) {
                $phoneNumber->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, MobileMoneyAccount>
     */
    public function getMobileAccounts(): Collection
    {
        return $this->mobileAccounts;
    }

    public function addMobileAccount(MobileMoneyAccount $mobileAccount): static
    {
        if (!$this->mobileAccounts->contains($mobileAccount)) {
            $this->mobileAccounts->add($mobileAccount);
            $mobileAccount->setUser($this);
        }
        return $this;
    }

    public function removeMobileAccount(MobileMoneyAccount $mobileAccount): static
    {
        if ($this->mobileAccounts->removeElement($mobileAccount)) {
            if ($mobileAccount->getUser() === $this) {
                $mobileAccount->setUser(null);
            }
        }
        return $this;
    }
}

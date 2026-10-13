<?php

#affiliate-tracker-symfony/backend/src/Entity/Affiliate.php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AffiliateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AffiliateRepository::class)]
#[ORM\Table(name: 'affiliates')]
#[ORM\UniqueConstraint(name: 'uniq_affiliates_web_id', columns: ['web_id'])]
#[UniqueEntity(fields: ['webId'], message: 'This web_id is already taken.')]
class Affiliate
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_BLOCKED  = 'blocked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'affiliate', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Публичный ID веба, используется в ссылках:
     * https://track.domain.com/click?offer=123&web_id=WEB_ABC
     */
    #[ORM\Column(name: 'web_id', length: 64, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 64)]
    private string $webId;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_BLOCKED])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'postback_url', length: 512, nullable: true)]
    #[Assert\Url(requireTld: true)]
    private ?string $postbackUrl = null;

    /**
     * Секрет для HMAC-подписи postback'ов от advertiser'а.
     * Генерируется автоматически.
     */
    #[ORM\Column(length: 64)]
    private string $secret;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $balance = '0.00';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Offer>
     */
    #[ORM\ManyToMany(targetEntity: Offer::class, inversedBy: 'affiliates')]
    #[ORM\JoinTable(name: 'affiliate_offers')]
    private Collection $offers;

    public function __construct()
    {
        $this->webId = 'web_' . Uuid::v4()->toBase32();
        $this->secret = bin2hex(random_bytes(32));
        $this->createdAt = new \DateTimeImmutable();
        $this->offers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getWebId(): string
    {
        return $this->webId;
    }

    public function setWebId(string $webId): self
    {
        $this->webId = $webId;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getPostbackUrl(): ?string
    {
        return $this->postbackUrl;
    }

    public function setPostbackUrl(?string $postbackUrl): self
    {
        $this->postbackUrl = $postbackUrl;

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): self
    {
        $this->offers->removeElement($offer);

        return $this;
    }
}
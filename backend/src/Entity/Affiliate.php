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
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE  = 'active';
    public const STATUS_BLOCKED = 'blocked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'affiliate', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

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

    #[ORM\Column(length: 64)]
    private string $secret;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $balance = '0.00';

    #[ORM\Column(type: 'timestamptz')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'timestamptz', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, AffiliateOffer>
     */
    #[ORM\OneToMany(mappedBy: 'affiliate', targetEntity: AffiliateOffer::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $affiliateOffers;

    public function __construct()
    {
        $this->webId = 'web_' . Uuid::v4()->toBase32();
        $this->secret = bin2hex(random_bytes(32));
        $this->createdAt = new \DateTimeImmutable();
        $this->affiliateOffers = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getWebId(): string { return $this->webId; }
    public function setWebId(string $webId): self { $this->webId = $webId; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function getPostbackUrl(): ?string { return $this->postbackUrl; }
    public function setPostbackUrl(?string $postbackUrl): self { $this->postbackUrl = $postbackUrl; return $this; }
    public function getSecret(): string { return $this->secret; }
    public function getBalance(): string { return $this->balance; }
    public function setBalance(string $balance): self { $this->balance = $balance; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, AffiliateOffer>
     */
    public function getAffiliateOffers(): Collection
    {
        return $this->affiliateOffers;
    }

    public function addAffiliateOffer(AffiliateOffer $affiliateOffer): self
    {
        if (!$this->affiliateOffers->contains($affiliateOffer)) {
            $this->affiliateOffers->add($affiliateOffer);
        }

        return $this;
    }

    public function removeAffiliateOffer(AffiliateOffer $affiliateOffer): self
    {
        $this->affiliateOffers->removeElement($affiliateOffer);

        return $this;
    }

    /**
     * Хелпер: офферы, которые взял веб
     *
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->affiliateOffers->map(fn (AffiliateOffer $ao) => $ao->getOffer());
    }

    /**
     * Хелпер: взять оффер (создаёт AffiliateOffer с секретом, если ещё нет)
     */
    public function takeOffer(Offer $offer): AffiliateOffer
    {
        foreach ($this->affiliateOffers as $ao) {
            if ($ao->getOffer()->getId() === $offer->getId()) {
                return $ao;
            }
        }

        $affiliateOffer = new AffiliateOffer($this, $offer);
        $this->affiliateOffers->add($affiliateOffer);

        return $affiliateOffer;
    }
}
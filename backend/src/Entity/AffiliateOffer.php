<?php
#affiliate-tracker-symfony/backend/src/Entity/AffiliateOffer.php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AffiliateOfferRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Collection;

#[ORM\Entity(repositoryClass: AffiliateOfferRepository::class)]
#[ORM\Table(name: 'affiliate_offers')]
#[ORM\UniqueConstraint(name: 'uniq_affiliate_offers_pair', columns: ['affiliate_id', 'offer_id'])]
#[ORM\Index(name: 'idx_affiliate_offers_offer_id', columns: ['offer_id'])]
class AffiliateOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Affiliate::class, inversedBy: 'affiliateOffers')]
    #[ORM\JoinColumn(name: 'affiliate_id', nullable: false, onDelete: 'CASCADE')]
    private Affiliate $affiliate;

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'affiliateOffers')]
    #[ORM\JoinColumn(name: 'offer_id', nullable: false, onDelete: 'CASCADE')]
    private Offer $offer;

    #[ORM\Column(name: 'postback_secret', length: 64)]
    private string $postbackSecret;

    #[ORM\Column(type: 'timestamptz')]
    private \DateTimeImmutable $createdAt;

    // #[ORM\OneToMany(mappedBy:'clicks', targetEntity: Click::class)]
    // #[ORM\JoinColumn()]
    // private Collection $clicks;

    public function __construct(Affiliate $affiliate, Offer $offer)
    {
        $this->affiliate = $affiliate;
        $this->offer = $offer;
        $this->postbackSecret = bin2hex(random_bytes(32));
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAffiliate(): Affiliate
    {
        return $this->affiliate;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function getPostbackSecret(): string
    {
        return $this->postbackSecret;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function regenerateSecret(): self
    {
        $this->postbackSecret = bin2hex(random_bytes(32));

        return $this;
    }
}
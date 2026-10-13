<?php

#affiliate-tracker-symfony/backend/src/Entity/Conversion.php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConversionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversionRepository::class)]
#[ORM\Table(name: 'conversions')]
#[ORM\Index(name: 'idx_conversions_click_id', columns: ['click_id'])]
#[ORM\Index(name: 'idx_conversions_created_at', columns: ['created_at'])]
class Conversion
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'click_id', length: 36)]
    private string $clickId;

    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(name: 'offer_id', nullable: false, onDelete: 'RESTRICT')]
    private Offer $offer;

    #[ORM\ManyToOne(targetEntity: Affiliate::class)]
    #[ORM\JoinColumn(name: 'affiliate_id', nullable: false, onDelete: 'RESTRICT')]
    private Affiliate $affiliate;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $payout;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $meta = [];

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClickId(): string
    {
        return $this->clickId;
    }

    public function setClickId(string $clickId): self
    {
        $this->clickId = $clickId;

        return $this;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer): self
    {
        $this->offer = $offer;

        return $this;
    }

    public function getAffiliate(): Affiliate
    {
        return $this->affiliate;
    }

    public function setAffiliate(Affiliate $affiliate): self
    {
        $this->affiliate = $affiliate;

        return $this;
    }

    public function getPayout(): string
    {
        return $this->payout;
    }

    public function setPayout(string $payout): self
    {
        $this->payout = $payout;

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

    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
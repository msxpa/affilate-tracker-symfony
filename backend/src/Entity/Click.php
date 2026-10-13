<?php

#affiliate-tracker-symfony/backend/src/Entity/Click.php

/*
 Важно: у Click нет #[ORM\GeneratedValue]. Причина — партиционированная таблица. PostgreSQL требует, 
 чтобы PK включал partition key (created_at). Мы будем писать id вручную через sequence + триггер, 
 либо через RETURNING в воркере. Это будет в миграции.
*/

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClickRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClickRepository::class)]
#[ORM\Table(name: 'clicks')]
class Click
{
    #[ORM\Id]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'click_id', length: 36, unique: true)]
    private string $clickId;

    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(name: 'offer_id', nullable: false, onDelete: 'RESTRICT')]
    private Offer $offer;

    #[ORM\ManyToOne(targetEntity: Affiliate::class)]
    #[ORM\JoinColumn(name: 'affiliate_id', nullable: false, onDelete: 'RESTRICT')]
    private Affiliate $affiliate;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(name: 'user_agent', length: 512, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $referer = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $params = [];

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

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
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

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setReferer(?string $referer): self
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
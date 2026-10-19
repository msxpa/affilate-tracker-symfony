<?php

#affiliate-tracker-symfony/backend/src/Entity/Offer.php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
#[ORM\Table(name: 'offers')]
#[ORM\UniqueConstraint(name: 'uniq_offers_slug', columns: ['slug'])]
#[UniqueEntity(fields: ['slug'], message: 'This slug is already taken.')]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 120)]
    private string $name;

    #[ORM\Column(length: 120, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Slug must contain only lowercase letters, digits and dashes.')]
    private string $slug;

    #[ORM\Column(name: 'destination_url', length: 1024)]
    #[Assert\NotBlank]
    #[Assert\Url(requireTld: true)]
    private string $destinationUrl;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $payout = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $revenue = '0.00';

    #[ORM\Column]
    private bool $active = true;

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'allowed_countries', type: Types::JSON, options: ['jsonb' => true, 'default' => '[]'])]
    private array $allowedCountries = [];

    // #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => 'now()'])]
    // private \DateTimeImmutable $createdAt;

    // #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    // private ?\DateTimeImmutable $updatedAt = null;

    // #[ORM\Column(type: 'timestamptz', options: ['default' => 'now()'])]
    #[ORM\Column(type: 'timestamptz')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'timestamptz', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Affiliate>
     */
    #[ORM\ManyToMany(targetEntity: Affiliate::class, mappedBy: 'offers')]
    private Collection $affiliates;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->affiliates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDestinationUrl(): string
    {
        return $this->destinationUrl;
    }

    public function setDestinationUrl(string $destinationUrl): self
    {
        $this->destinationUrl = $destinationUrl;

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

    public function getRevenue(): string
    {
        return $this->revenue;
    }

    public function setRevenue(string $revenue): self
    {
        $this->revenue = $revenue;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAllowedCountries(): array
    {
        return $this->allowedCountries;
    }

    /**
     * @param list<string> $allowedCountries
     */
    public function setAllowedCountries(array $allowedCountries): self
    {
        $this->allowedCountries = array_values(array_map('strtoupper', $allowedCountries));

        return $this;
    }

    public function isAllowedInCountry(?string $country): bool
    {
        if ($this->allowedCountries === []) {
            return true;
        }

        if ($country === null) {
            return false;
        }

        return \in_array(strtoupper($country), $this->allowedCountries, true);
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
     * @return Collection<int, Affiliate>
     */
    public function getAffiliates(): Collection
    {
        return $this->affiliates;
    }

    public function addAffiliate(Affiliate $affiliate): self
    {
        if (!$this->affiliates->contains($affiliate)) {
            $this->affiliates->add($affiliate);
        }

        return $this;
    }

    public function removeAffiliate(Affiliate $affiliate): self
    {
        $this->affiliates->removeElement($affiliate);

        return $this;
    }
}

// declare(strict_types=1);

// namespace App\Entity;

// use App\Repository\OfferRepository;
// use Doctrine\Common\Collections\ArrayCollection;
// use Doctrine\Common\Collections\Collection;
// use Doctrine\DBAL\Types\Types;
// use Doctrine\ORM\Mapping as ORM;
// use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
// use Symfony\Component\Validator\Constraints as Assert;

// #[ORM\Entity(repositoryClass: OfferRepository::class)]
// #[ORM\Table(name: 'offers')]
// #[ORM\UniqueConstraint(name: 'uniq_offers_slug', columns: ['slug'])]
// #[UniqueEntity(fields: ['slug'], message: 'This slug is already taken.')]
// class Offer
// {
//     #[ORM\Id]
//     #[ORM\GeneratedValue]
//     #[ORM\Column]
//     private ?int $id = null;

//     #[ORM\Column(length: 120)]
//     #[Assert\NotBlank]
//     #[Assert\Length(min: 3, max: 120)]
//     private string $name;

//     #[ORM\Column(length: 120, unique: true)]
//     #[Assert\NotBlank]
//     #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Slug must contain only lowercase letters, digits and dashes.')]
//     private string $slug;

//     #[ORM\Column(name: 'destination_url', length: 1024)]
//     #[Assert\NotBlank]
//     #[Assert\Url(requireTld: true)]
//     private string $destinationUrl;

//     /**
//      * Сколько платим вебу за конверсию.
//      */
//     #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
//     #[Assert\PositiveOrZero]
//     private string $payout = '0.00';

//     /**
//      * Сколько получаем от advertiser'а.
//      */
//     #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
//     #[Assert\PositiveOrZero]
//     private string $revenue = '0.00';

//     #[ORM\Column]
//     private bool $active = true;

//     /**
//      * ISO 3166-1 alpha-2 country codes, например ["RU", "KZ"].
//      * Пустой массив = без гео-таргетинга.
//      *
//      * @var list<string>
//      */
//     #[ORM\Column(name: 'allowed_countries', type: Types::JSON)]
//     private array $allowedCountries = [];

//     #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
//     private \DateTimeImmutable $createdAt;

//     #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
//     private ?\DateTimeImmutable $updatedAt = null;

//     /**
//      * @var Collection<int, Affiliate>
//      */
//     #[ORM\ManyToMany(targetEntity: Affiliate::class, mappedBy: 'offers')]
//     private Collection $affiliates;

//     public function __construct()
//     {
//         $this->createdAt = new \DateTimeImmutable();
//         $this->affiliates = new ArrayCollection();
//     }

//     public function getId(): ?int
//     {
//         return $this->id;
//     }

//     public function getName(): string
//     {
//         return $this->name;
//     }

//     public function setName(string $name): self
//     {
//         $this->name = $name;

//         return $this;
//     }

//     public function getSlug(): string
//     {
//         return $this->slug;
//     }

//     public function setSlug(string $slug): self
//     {
//         $this->slug = $slug;

//         return $this;
//     }

//     public function getDestinationUrl(): string
//     {
//         return $this->destinationUrl;
//     }

//     public function setDestinationUrl(string $destinationUrl): self
//     {
//         $this->destinationUrl = $destinationUrl;

//         return $this;
//     }

//     public function getPayout(): string
//     {
//         return $this->payout;
//     }

//     public function setPayout(string $payout): self
//     {
//         $this->payout = $payout;

//         return $this;
//     }

//     public function getRevenue(): string
//     {
//         return $this->revenue;
//     }

//     public function setRevenue(string $revenue): self
//     {
//         $this->revenue = $revenue;

//         return $this;
//     }

//     public function isActive(): bool
//     {
//         return $this->active;
//     }

//     public function setActive(bool $active): self
//     {
//         $this->active = $active;

//         return $this;
//     }

//     /**
//      * @return list<string>
//      */
//     public function getAllowedCountries(): array
//     {
//         return $this->allowedCountries;
//     }

//     /**
//      * @param list<string> $allowedCountries
//      */
//     public function setAllowedCountries(array $allowedCountries): self
//     {
//         $this->allowedCountries = array_values(array_map('strtoupper', $allowedCountries));

//         return $this;
//     }

//     public function isAllowedInCountry(?string $country): bool
//     {
//         if ($this->allowedCountries === []) {
//             return true;
//         }

//         if ($country === null) {
//             return false;
//         }

//         return \in_array(strtoupper($country), $this->allowedCountries, true);
//     }

//     public function getCreatedAt(): \DateTimeImmutable
//     {
//         return $this->createdAt;
//     }

//     public function getUpdatedAt(): ?\DateTimeImmutable
//     {
//         return $this->updatedAt;
//     }

//     #[ORM\PreUpdate]
//     public function touchUpdatedAt(): void
//     {
//         $this->updatedAt = new \DateTimeImmutable();
//     }

//     /**
//      * @return Collection<int, Affiliate>
//      */
//     public function getAffiliates(): Collection
//     {
//         return $this->affiliates;
//     }

//     public function addAffiliate(Affiliate $affiliate): self
//     {
//         if (!$this->affiliates->contains($affiliate)) {
//             $this->affiliates->add($affiliate);
//         }

//         return $this;
//     }

//     public function removeAffiliate(Affiliate $affiliate): self
//     {
//         $this->affiliates->removeElement($affiliate);

//         return $this;
//     }
// }
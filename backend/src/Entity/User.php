<?php
#affiliate-tracker-symfony/backend/src/Entity/User.php

/*
Что тут важно
#[ORM\Table(name: 'users')] — PostgreSQL резервирует слово user, 
поэтому таблица называется users (множественное число, не конфликтует).

#[UniqueEntity] — валидация уникальности email на уровне Symfony (не только БД).

ROLE_USER добавляется автоматически в getRoles() — это стандарт Symfony.

createdAt — DATETIME_IMMUTABLE, автоматически при создании.

updatedAt — через #[ORM\PreUpdate] lifecycle callback.

affiliate — 1:1 с Affiliate, каскад persist/remove.

implements UserInterface, PasswordAuthenticatedUserInterface — обязательно для Symfony Security.
*/

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_AFFILIATE = 'ROLE_AFFILIATE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    // #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => 'now()'])]
    // private \DateTimeImmutable $createdAt;

    // #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    // private ?\DateTimeImmutable $updatedAt = null;

    //#[ORM\Column(type: 'timestamptz', options: ['default' => 'now()'])]
    #[ORM\Column(type: 'timestamptz')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'timestamptz', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Affiliate::class, cascade: ['persist', 'remove'])]
    private ?Affiliate $affiliate = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): self
    {
        if (!\in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return \in_array($role, $this->getRoles(), true);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
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

    public function getAffiliate(): ?Affiliate
    {
        return $this->affiliate;
    }

    public function setAffiliate(?Affiliate $affiliate): self
    {
        $this->affiliate = $affiliate;

        return $this;
    }
}

// declare(strict_types=1);

// namespace App\Entity;

// use App\Repository\UserRepository;
// use Doctrine\Common\Collections\ArrayCollection;
// use Doctrine\Common\Collections\Collection;
// use Doctrine\DBAL\Types\Types;
// use Doctrine\ORM\Mapping as ORM;
// use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
// use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
// use Symfony\Component\Security\Core\User\UserInterface;
// use Symfony\Component\Validator\Constraints as Assert;

// #[ORM\Entity(repositoryClass: UserRepository::class)]
// #[ORM\Table(name: 'users')]
// #[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
// #[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
// class User implements UserInterface, PasswordAuthenticatedUserInterface
// {
//     public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
//     public const ROLE_ADMIN = 'ROLE_ADMIN';
//     public const ROLE_AFFILIATE = 'ROLE_AFFILIATE';

//     #[ORM\Id]
//     #[ORM\GeneratedValue]
//     #[ORM\Column]
//     private ?int $id = null;

//     #[ORM\Column(length: 180)]
//     #[Assert\NotBlank]
//     #[Assert\Email]
//     private string $email;

//     /**
//      * @var list<string>
//      */
//     #[ORM\Column]
//     private array $roles = [];

//     #[ORM\Column]
//     private string $password;

//     #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
//     private \DateTimeImmutable $createdAt;

//     #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
//     private ?\DateTimeImmutable $updatedAt = null;

//     #[ORM\OneToOne(mappedBy: 'user', targetEntity: Affiliate::class, cascade: ['persist', 'remove'])]
//     private ?Affiliate $affiliate = null;

//     public function __construct()
//     {
//         $this->createdAt = new \DateTimeImmutable();
//     }

//     public function getId(): ?int
//     {
//         return $this->id;
//     }

//     public function getEmail(): string
//     {
//         return $this->email;
//     }

//     public function setEmail(string $email): self
//     {
//         $this->email = $email;

//         return $this;
//     }

//     public function getUserIdentifier(): string
//     {
//         return $this->email;
//     }

//     /**
//      * @return list<string>
//      */
//     public function getRoles(): array
//     {
//         $roles = $this->roles;
//         $roles[] = 'ROLE_USER';

//         return array_values(array_unique($roles));
//     }

//     /**
//      * @param list<string> $roles
//      */
//     public function setRoles(array $roles): self
//     {
//         $this->roles = $roles;

//         return $this;
//     }

//     public function addRole(string $role): self
//     {
//         if (!\in_array($role, $this->roles, true)) {
//             $this->roles[] = $role;
//         }

//         return $this;
//     }

//     public function hasRole(string $role): bool
//     {
//         return \in_array($role, $this->getRoles(), true);
//     }

//     public function getPassword(): string
//     {
//         return $this->password;
//     }

//     public function setPassword(string $password): self
//     {
//         $this->password = $password;

//         return $this;
//     }

//     public function eraseCredentials(): void
//     {
//         // Ничего не делаем — у нас нет plainPassword
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

//     public function getAffiliate(): ?Affiliate
//     {
//         return $this->affiliate;
//     }

//     public function setAffiliate(?Affiliate $affiliate): self
//     {
//         $this->affiliate = $affiliate;

//         return $this;
//     }
// }
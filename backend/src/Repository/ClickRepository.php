<?php

#affiliate-tracker-symfony/backend/src/Repository/ClickRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Click;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Click>
 */
class ClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Click::class);
    }
}
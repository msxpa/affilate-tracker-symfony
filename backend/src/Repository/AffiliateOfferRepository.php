<?php
#affiliate-tracker-symfony/backend/src/Repository/AffiliateOfferRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AffiliateOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AffiliateOffer>
 */
class AffiliateOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffiliateOffer::class);
    }

    public function findForPair(int $affiliateId, int $offerId): ?AffiliateOffer
    {
        return $this->findOneBy([
            'affiliate' => $affiliateId,
            'offer' => $offerId,
        ]);
    }

}
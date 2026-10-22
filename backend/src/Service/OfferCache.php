<?php
#affiliate-tracker-symfony/backend/src/Service/OfferCache.php
declare(strict_types=1);

namespace App\Service;

use App\Repository\OfferRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class OfferCache
{
    public function __construct(
        private readonly OfferRepository $offerRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array{id: int, destinationUrl: string, payout: string, revenue: string, allowedCountries: list<string>}|null
     */
    // public function getActiveOffer(int $offerId): ?array
    // {
    //     return $this->cache->get('offer_'.$offerId, function (ItemInterface $item) use ($offerId): ?array {
    //         $item->expiresAfter(60);

    //         $offer = $this->offerRepository->find($offerId);

    //         if ($offer === null || !$offer->isActive()) {
    //             return null;
    //         }

    //         return [
    //             'id' => $offer->getId(),
    //             'destinationUrl' => $offer->getDestinationUrl(),
    //             'payout' => $offer->getPayout(),
    //             'revenue' => $offer->getRevenue(),
    //             'allowedCountries' => $offer->getAllowedCountries(),
    //         ];
    //     });
    // }

    public function getActiveOffer(int $offerId): ?array
    {
        $cached = $this->cache->get('offer_'.$offerId, function (ItemInterface $item) use ($offerId): array {
            $item->expiresAfter(60);

            $offer = $this->offerRepository->find($offerId);

            if ($offer === null || !$offer->isActive()) {
                return ['not_found' => true];
            }

            return [
                'not_found' => false,
                'id' => $offer->getId(),
                'destinationUrl' => $offer->getDestinationUrl(),
                'payout' => $offer->getPayout(),
                'revenue' => $offer->getRevenue(),
                'allowedCountries' => $offer->getAllowedCountries(),
            ];
        });

        return $cached['not_found'] ? null : $cached;
    }

    public function invalidate(int $offerId): void
    {
        $this->cache->delete('offer_'.$offerId);
    }
}
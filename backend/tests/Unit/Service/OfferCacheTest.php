<?php
#affiliate-tracker-symfony/backend/tests/Unit/Service/OfferCacheTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use App\Service\OfferCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;

final class OfferCacheTest extends TestCase
{
    private function createOffer(int $id, bool $active = true): Offer
    {
        $offer = new Offer();
        $offer->setName('Test Offer');
        $offer->setSlug('test-offer');
        $offer->setDestinationUrl('https://example.com/offer');
        $offer->setPayout('10.00');
        $offer->setRevenue('15.00');
        $offer->setActive($active);

        // Set ID через reflection
        $reflection = new \ReflectionClass($offer);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($offer, $id);

        return $offer;
    }

    public function testGetActiveOfferCachesResult(): void
    {
        $offer = $this->createOffer(1, true);

        $repository = $this->createMock(OfferRepository::class);
        $repository->expects($this->once())  // только ОДИН раз — второй раз из кэша
            ->method('find')
            ->with(1)
            ->willReturn($offer);

        $cache = new ArrayAdapter();
        $offerCache = new OfferCache($repository, $cache);

        // Первый вызов — из репозитория
        $result1 = $offerCache->getActiveOffer(1);
        $this->assertNotNull($result1);
        $this->assertSame(1, $result1['id']);
        $this->assertSame('https://example.com/offer', $result1['destinationUrl']);

        // Второй вызов — из кэша (репозиторий НЕ вызывается)
        $result2 = $offerCache->getActiveOffer(1);
        $this->assertSame($result1, $result2);
    }

    // public function testGetInactiveOfferReturnsNull(): void
    // {
    //     $offer = $this->createOffer(2, false);

    //     $repository = $this->createMock(OfferRepository::class);
    //     // $repository->method('find')->with(2)->willReturn($offer);
    //     $repository->expects($this->once())      // ← обязательный expects()
    //         ->method('find')
    //         ->with(2)
    //         ->willReturn($offer);

    //     $cache = new ArrayAdapter();
    //     $offerCache = new OfferCache($repository, $cache);

    //     $result = $offerCache->getActiveOffer(2);

    //     $this->assertNull($result);
    // }

    public function testGetInactiveOfferReturnsNull(): void
    {
        $offer = $this->createOffer(2, false);

        $repository = $this->createMock(OfferRepository::class);
        $repository->expects($this->once())     // ← добавлен expects()
            ->method('find')
            ->with(2)
            ->willReturn($offer);

        $cache = new ArrayAdapter();
        $offerCache = new OfferCache($repository, $cache);

        $result = $offerCache->getActiveOffer(2);

        $this->assertNull($result);
    }

    // public function testGetNonExistentOfferReturnsNull(): void
    // {
    //     $repository = $this->createMock(OfferRepository::class);
    //     $repository->method('find')->with(999)->willReturn(null);

    //     $cache = new ArrayAdapter();
    //     $offerCache = new OfferCache($repository, $cache);

    //     $result = $offerCache->getActiveOffer(999);

    //     $this->assertNull($result);
    // }

    public function testGetNonExistentOfferReturnsNull(): void
    {
        $repository = $this->createMock(OfferRepository::class);
        $repository->expects($this->once())     // ← добавлен expects()
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $cache = new ArrayAdapter();
        $offerCache = new OfferCache($repository, $cache);

        $result = $offerCache->getActiveOffer(999);

        $this->assertNull($result);
    }

    public function testInvalidateClearsCache(): void
    {
        $offer = $this->createOffer(3, true);

        $repository = $this->createMock(OfferRepository::class);
        $repository->expects($this->exactly(2))  // 2 раза: до инвалидации и после
            ->method('find')
            ->with(3)
            ->willReturn($offer);

        $cache = new ArrayAdapter();
        $offerCache = new OfferCache($repository, $cache);

        $offerCache->getActiveOffer(3);   // кэшируется
        $offerCache->invalidate(3);        // кэш очищен
        $offerCache->getActiveOffer(3);   // снова из репозитория
    }
}
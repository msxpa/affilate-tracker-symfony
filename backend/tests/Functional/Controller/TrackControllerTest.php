<?php
#affiliate-tracker-symfony/backend/tests/Functional/Controller/TrackControllerTest.php
declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Offer;
use App\Service\OfferCache;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class TrackControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $em;
    private OfferCache $offerCache;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->offerCache = $container->get(OfferCache::class);

        $conn = $this->em->getConnection();
        $conn->executeStatement('TRUNCATE TABLE clicks RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE affiliate_offers RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE offers RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE affiliates RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
    }

    private function createOffer(bool $active = true, string $slug = 'test-offer'): Offer
    {
        $offer = new Offer();
        $offer->setName('Test Offer '.$slug);
        $offer->setSlug($slug);
        $offer->setDestinationUrl('https://example.com/offer');
        $offer->setPayout('10.00');
        $offer->setRevenue('15.00');
        $offer->setActive($active);

        $this->em->persist($offer);
        $this->em->flush();

        // Инвалидируем кэш, чтобы тест не зависел от предыдущих запусков
        $this->offerCache->invalidate($offer->getId());

        return $offer;
    }

    public function testValidClickRedirects(): void
    {
        $offer = $this->createOffer(true);
        $clickId = Uuid::v4()->toRfc4122();

        $this->client->request('GET', '/click', [
            'offer' => $offer->getId(),
            'web_id' => 'web_test123',
            'clickid' => $clickId,
        ]);

        $this->assertResponseStatusCodeSame(302);

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('https://example.com/offer', $location);
        $this->assertStringContainsString('click_id='.$clickId, $location);
        $this->assertStringContainsString('web_id=web_test123', $location);
    }

    public function testClickWithoutOfferIdReturns400(): void
    {
        $this->client->request('GET', '/click', [
            'web_id' => 'web_test123',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testClickWithoutWebIdReturns400(): void
    {
        $this->client->request('GET', '/click', [
            'offer' => 1,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testClickForInactiveOfferReturns404(): void
    {
        $offer = $this->createOffer(false);

        $this->client->request('GET', '/click', [
            'offer' => $offer->getId(),
            'web_id' => 'web_test123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testClickForNonExistentOfferReturns404(): void
    {
        $this->client->request('GET', '/click', [
            'offer' => 99999,
            'web_id' => 'web_test123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    // public function testInvalidClickIdGeneratesUuid(): void
    // {
    //     $offer = $this->createOffer(true);

    //     $this->client->request('GET', '/click', [
    //         'offer' => $offer->getId(),
    //         'web_id' => 'web_test123',
    //         'clickid' => 'not-a-uuid',
    //     ]);

    //     $this->assertResponseStatusCodeSame(302);

    //     $location = $this->client->getResponse()->headers->get('Location');
    //     $this->assertNotNull($location);

    //     // Извлекаем click_id из Location
    //     parse_str(parse_url($location, PHP_URL_QUERY), $query);
    //     $clickId = $query['click_id'] ?? '';

    //     $this->assertTrue(Uuid::isValid($clickId), 'Click ID должен быть UUID');
    //     $this->assertNotSame('not-a-uuid', $clickId);
    // }

    public function testInvalidClickIdGeneratesUuid(): void
    {
        $offer = $this->createOffer(true);

        $this->client->request('GET', '/click', [
            'offer' => $offer->getId(),
            'web_id' => 'web_test123',
            'clickid' => 'not-a-uuid',
        ]);

        $this->assertResponseStatusCodeSame(302);

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);

        preg_match('/click_id=([a-f0-9-]{36})/i', $location, $matches);

        $this->assertNotEmpty($matches, 'click_id не найден в Location: '.$location);
        $this->assertTrue(Uuid::isValid($matches[1]));
        $this->assertNotSame('not-a-uuid', $matches[1]);
    }

    // public function testClickWithoutClickIdGeneratesUuid(): void
    // {
    //     $offer = $this->createOffer(true);

    //     $this->client->request('GET', '/click', [
    //         'offer' => $offer->getId(),
    //         'web_id' => 'web_test123',
    //     ]);

    //     $this->assertResponseStatusCodeSame(302);

    //     $location = $this->client->getResponse()->headers->get('Location');
    //     parse_str(parse_url($location, PHP_URL_QUERY), $query);
    //     $clickId = $query['click_id'] ?? '';

    //     $this->assertTrue(Uuid::isValid($clickId));
    // }

    public function testClickWithoutClickIdGeneratesUuid(): void
    {
        $offer = $this->createOffer(true);

        $this->client->request('GET', '/click', [
            'offer' => $offer->getId(),
            'web_id' => 'web_test123',
        ]);

        $this->assertResponseStatusCodeSame(302);

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);

        // Regex вместо parse_str
        preg_match('/click_id=([a-f0-9-]{36})/i', $location, $matches);

        $this->assertNotEmpty($matches, 'click_id не найден в Location: '.$location);
        $this->assertTrue(Uuid::isValid($matches[1]));
    }
}
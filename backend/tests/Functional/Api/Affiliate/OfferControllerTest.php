<?php
#affiliate-tracker-symfony/backend/tests/Functional/Api/Affiliate/OfferControllerTest.php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Affiliate;

use App\Entity\Affiliate;
use App\Entity\Offer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OfferControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $em;
    private JWTTokenManagerInterface $jwtManager;

    // protected function setUp(): void
    // {
    //     $this->client = static::createClient();
    //     $container = static::getContainer();

    //     $this->em = $container->get(EntityManagerInterface::class);
    //     $this->jwtManager = $container->get(JWTTokenManagerInterface::class);

    //     $this->em->getConnection()->executeStatement('TRUNCATE TABLE offers RESTART IDENTITY CASCADE');
    //     $this->em->getConnection()->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
    //     $this->em->getConnection()->executeStatement('TRUNCATE TABLE affiliates RESTART IDENTITY CASCADE');
    // }

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->jwtManager = $container->get(JWTTokenManagerInterface::class);

        $conn = $this->em->getConnection();
        $conn->executeStatement('TRUNCATE TABLE clicks RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE offers RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE affiliates RESTART IDENTITY CASCADE');
    }

    private function createActiveWeb(): array
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('web@test.com');
        $user->setRoles([User::ROLE_AFFILIATE]);
        $user->setPassword($hasher->hashPassword($user, 'password123'));

        $affiliate = new Affiliate();
        $affiliate->setUser($user);
        $affiliate->setStatus(Affiliate::STATUS_ACTIVE);

        $this->em->persist($user);
        $this->em->persist($affiliate);
        $this->em->flush();

        return [$user, $affiliate, $this->jwtManager->create($user)];
    }

    private function createOffer(bool $active = true, string $slug = 'test-offer'): Offer
{
        $offer = new Offer();
        $offer->setName('Test Offer '.$slug);
        $offer->setSlug($slug);
        $offer->setDestinationUrl('https://example.com');
        $offer->setPayout('10.00');
        $offer->setRevenue('15.00');
        $offer->setActive($active);

        $this->em->persist($offer);
        $this->em->flush();

        return $offer;
    }

    // public function testListActiveOffers(): void
    // {
    //     [$user, $affiliate, $token] = $this->createActiveWeb();
    //     $this->createOffer(true);
    //     $this->createOffer(false); // неактивный

    //     $this->client->request('GET', '/api/affiliate/offers', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

    //     $this->assertResponseIsSuccessful();

    //     $data = json_decode($this->client->getResponse()->getContent(), true);
    //     $this->assertSame(1, $data['total']); // только активный
    //     $this->assertSame('Test Offer', $data['items'][0]['name']);
    // }

    public function testListActiveOffers(): void
    {
        [$user, $affiliate, $token] = $this->createActiveWeb();
        $activeOffer = $this->createOffer(true, 'active-offer');
        $inactiveOffer = $this->createOffer(false, 'inactive-offer');

        $this->client->request('GET', '/api/affiliate/offers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['total']);
        $this->assertSame($activeOffer->getId(), $data['items'][0]['id']);
        $this->assertSame('active-offer', $data['items'][0]['slug']);
    }

    public function testTakeActiveOffer(): void
    {
        [$user, $affiliate, $token] = $this->createActiveWeb();
        $offer = $this->createOffer(true);

        $this->client->request('POST', '/api/affiliate/offers/'.$offer->getId().'/take', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('link', $data);
        $this->assertStringContainsString('offer='.$offer->getId(), $data['link']);
        $this->assertStringContainsString('web_id='.$affiliate->getWebId(), $data['link']);
        $this->assertStringContainsString('clickid={clickid}', $data['link']);
    }

    public function testTakeInactiveOfferForbidden(): void
    {
        [$user, $affiliate, $token] = $this->createActiveWeb();
        $offer = $this->createOffer(false); // неактивный

        $this->client->request('POST', '/api/affiliate/offers/'.$offer->getId().'/take', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetLink(): void
    {
        [$user, $affiliate, $token] = $this->createActiveWeb();
        $offer = $this->createOffer(true);

        $this->client->request('GET', '/api/affiliate/links?offer='.$offer->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('link', $data);
        $this->assertStringContainsString('web_id='.$affiliate->getWebId(), $data['link']);
    }
}
<?php
#affiliate-tracker-symfony/backend/tests/Functional/Api/Admin/OfferControllerTest.php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OfferControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $em;
    private JWTTokenManagerInterface $jwtManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->jwtManager = $container->get(JWTTokenManagerInterface::class);

        // Очищаем таблицы перед каждым тестом
        $this->em->getConnection()->executeStatement('TRUNCATE TABLE offers RESTART IDENTITY CASCADE');
        $this->em->getConnection()->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
    }

    private function createAdminToken(): string
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setRoles([User::ROLE_ADMIN]);
        $admin->setPassword($hasher->hashPassword($admin, 'admin123'));

        $this->em->persist($admin);
        $this->em->flush();

        return $this->jwtManager->create($admin);
    }

    private function createWebToken(): string
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $web = new User();
        $web->setEmail('web@test.com');
        $web->setRoles([User::ROLE_AFFILIATE]);
        $web->setPassword($hasher->hashPassword($web, 'password123'));

        $this->em->persist($web);
        $this->em->flush();

        return $this->jwtManager->create($web);
    }

    public function testCreateOfferAsAdmin(): void
    {
        $token = $this->createAdminToken();

        $this->client->request(
            'POST',
            '/api/admin/offers',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Test Offer',
                'slug' => 'test-offer',
                'destinationUrl' => 'https://example.com/offer',
                'payout' => '10.00',
                'revenue' => '15.00',
                'allowedCountries' => ['RU', 'KZ'],
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Test Offer', $data['name']);
        $this->assertSame('test-offer', $data['slug']);
        $this->assertTrue($data['active']);
        $this->assertSame(['RU', 'KZ'], $data['allowedCountries']);
    }

    public function testCreateOfferAsWebForbidden(): void
    {
        $token = $this->createWebToken();

        $this->client->request(
            'POST',
            '/api/admin/offers',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Hack', 'slug' => 'hack'])
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateOfferWithoutTokenUnauthorized(): void
    {
        $this->client->request(
            'POST',
            '/api/admin/offers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testValidationFailedReturns422(): void
    {
        $token = $this->createAdminToken();

        $this->client->request(
            'POST',
            '/api/admin/offers',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Validation failed', $data['error']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertNotEmpty($data['errors']);
    }

    public function testListOffers(): void
    {
        $token = $this->createAdminToken();

        // Создаём 2 оффера
        foreach ([1, 2] as $i) {
            $this->client->request(
                'POST',
                '/api/admin/offers',
                [],
                [],
                ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => "Offer $i",
                    'slug' => "offer-$i",
                    'destinationUrl' => 'https://example.com',
                    'payout' => '10.00',
                    'revenue' => '15.00',
                ])
            );
        }

        $this->client->request('GET', '/api/admin/offers', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(2, $data['total']);
        $this->assertCount(2, $data['items']);
    }
}
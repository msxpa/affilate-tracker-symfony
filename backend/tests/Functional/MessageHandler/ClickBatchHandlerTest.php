<?php
#affiliate-tracker-symfony/backend/tests/Functional/MessageHandler/ClickBatchHandlerTest.php
declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Affiliate;
use App\Entity\User;
use App\Message\ClickMessage;
use App\MessageHandler\ClickBatchHandler;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Uid\Uuid;

final class ClickBatchHandlerTest extends KernelTestCase
{
    private Connection $db;
    private ClickBatchHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->db = $container->get(Connection::class);
        $this->handler = $container->get(ClickBatchHandler::class);

        // Очистка
        $this->db->executeStatement('TRUNCATE TABLE clicks RESTART IDENTITY CASCADE');
        $this->db->executeStatement('TRUNCATE TABLE affiliate_offers RESTART IDENTITY CASCADE');
        $this->db->executeStatement('TRUNCATE TABLE affiliates RESTART IDENTITY CASCADE');
        $this->db->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
    }

    private function createAffiliate(string $webId = 'web_test123'): int
    {
        $this->db->executeStatement(
            'INSERT INTO users (email, roles, password, created_at) 
             VALUES (?, ?, ?, NOW())',
            ['web@test.com', '["ROLE_AFFILIATE"]', 'x']
        );

        $userId = (int) $this->db->lastInsertId();

        $this->db->executeStatement(
            'INSERT INTO affiliates (web_id, status, secret, balance, created_at, user_id) 
             VALUES (?, ?, ?, ?, NOW(), ?)',
            [$webId, 'active', 'secret', '0.00', $userId]
        );

        return (int) $this->db->lastInsertId();
    }

    // public function testProcessInsertsClick(): void
    // {
    //     $affiliateId = $this->createAffiliate('web_test123');

    //     $clickId = Uuid::v4()->toRfc4122();
    //     $message = new ClickMessage(
    //         clickId: $clickId,
    //         offerId: 1,
    //         webId: 'web_test123',
    //         ip: '1.2.3.4',
    //         userAgent: 'Mozilla/5.0',
    //         referer: 'https://google.com',
    //         params: ['utm_source' => 'test'],
    //         createdAt: new \DateTimeImmutable('2026-10-22 12:00:00'),
    //     );

    //     // Вызываем handler (через __invoke)
    //     // $ack = new Acknowledger(function () {}); //error bad
    //     $ack = new Acknowledger(ClickBatchHandler::class, function () {}); //test from __construct
    //     // $ack = $this->createMock(Acknowledger::class); //best TEST
    //     $this->handler->__invoke($message, $ack);

    //     // Проверяем, что клик в БД
    //     $click = $this->db->fetchAssociative(
    //         'SELECT * FROM clicks WHERE click_id = ?',
    //         [$clickId]
    //     );

    //     $this->assertNotFalse($click);
    //     $this->assertSame($clickId, $click['click_id']);
    //     $this->assertSame(1, (int) $click['offer_id']);
    //     $this->assertSame($affiliateId, (int) $click['affiliate_id']);
    //     $this->assertSame('1.2.3.4', $click['ip']);
    // }

    public function testProcessInsertsClick(): void
    {
        $affiliateId = $this->createAffiliate('web_test123');
        $clickId = Uuid::v4()->toRfc4122();

        $message = new ClickMessage(
            clickId: $clickId,
            offerId: 1,
            webId: 'web_test123',
            ip: '1.2.3.4',
            userAgent: 'Mozilla/5.0',
            referer: 'https://google.com',
            params: ['utm_source' => 'test'],
            createdAt: new \DateTimeImmutable('2026-10-22 12:00:00'),
        );

        $ack = new Acknowledger(ClickBatchHandler::class, function () {});

        // Вызываем process() напрямую — минуя батч
        $reflection = new \ReflectionMethod(ClickBatchHandler::class, 'process');
        $reflection->setAccessible(true);
        $reflection->invoke($this->handler, [[$message, $ack]]);

        $click = $this->db->fetchAssociative(
            'SELECT * FROM clicks WHERE click_id = ?',
            [$clickId]
        );

        $this->assertNotFalse($click);
        $this->assertSame($clickId, $click['click_id']);
        $this->assertSame(1, (int) $click['offer_id']);
        $this->assertSame($affiliateId, (int) $click['affiliate_id']);
        $this->assertSame('1.2.3.4', $click['ip']);
    }

    // public function testUnknownWebIdSkipped(): void
    // {
    //     $clickId = Uuid::v4()->toRfc4122();
    //     $message = new ClickMessage(
    //         clickId: $clickId,
    //         offerId: 1,
    //         webId: 'unknown_web',
    //         ip: '1.2.3.4',
    //         userAgent: null,
    //         referer: null,
    //         params: [],
    //         createdAt: new \DateTimeImmutable(),
    //     );

    //     // $ack = new Acknowledger(function () {});
    //     $ack = new Acknowledger(ClickBatchHandler::class, function () {}); //test from __construct
    //     // $ack = $this->createMock(Acknowledger::class); //best TEST
        
    //     $this->handler->__invoke($message, $ack);

    //     // Клик НЕ должен быть в БД (web_id не найден)
    //     $click = $this->db->fetchAssociative(
    //         'SELECT * FROM clicks WHERE click_id = ?',
    //         [$clickId]
    //     );

    //     $this->assertFalse($click);
    // }

    public function testUnknownWebIdSkipped(): void
    {
        $clickId = Uuid::v4()->toRfc4122();
        $message = new ClickMessage(
            clickId: $clickId,
            offerId: 1,
            webId: 'unknown_web',
            ip: '1.2.3.4',
            userAgent: null,
            referer: null,
            params: [],
            createdAt: new \DateTimeImmutable(),
        );

        $ack = new Acknowledger(ClickBatchHandler::class, function () {});

        $reflection = new \ReflectionMethod(ClickBatchHandler::class, 'process');
        $reflection->setAccessible(true);
        $reflection->invoke($this->handler, [[$message, $ack]]);

        $click = $this->db->fetchAssociative(
            'SELECT * FROM clicks WHERE click_id = ?',
            [$clickId]
        );

        $this->assertFalse($click);
    }

    // public function testDuplicateClickIgnored(): void
    // {
    //     $affiliateId = $this->createAffiliate('web_test123');
    //     $clickId = Uuid::v4()->toRfc4122();
    //     $createdAt = new \DateTimeImmutable('2026-10-22 12:00:00');

    //     // Первый клик
    //     $message1 = new ClickMessage(
    //         clickId: $clickId,
    //         offerId: 1,
    //         webId: 'web_test123',
    //         ip: '1.2.3.4',
    //         userAgent: null,
    //         referer: null,
    //         params: [],
    //         createdAt: $createdAt,
    //     );

    //     $ack1 = new Acknowledger(ClickBatchHandler::class, function () {});
    //     $this->handler->__invoke($message1, $ack1);

    //     // Второй клик с тем же clickId
    //     $message2 = new ClickMessage(
    //         clickId: $clickId,
    //         offerId: 1,
    //         webId: 'web_test123',
    //         ip: '5.6.7.8',
    //         userAgent: null,
    //         referer: null,
    //         params: [],
    //         createdAt: $createdAt,
    //     );

    //     $ack2 = new Acknowledger(ClickBatchHandler::class, function () {});
    //     $this->handler->__invoke($message2, $ack2);

    //     // В БД должен быть ТОЛЬКО один клик (ON CONFLICT DO NOTHING)
    //     $count = $this->db->fetchOne(
    //         'SELECT COUNT(*) FROM clicks WHERE click_id = ?',
    //         [$clickId]
    //     );

    //     $this->assertSame(1, (int) $count);
    // }

    public function testDuplicateClickIgnored(): void
    {
        $affiliateId = $this->createAffiliate('web_test123');
        $clickId = Uuid::v4()->toRfc4122();
        $createdAt = new \DateTimeImmutable('2026-10-22 12:00:00');

        $message1 = new ClickMessage(
            clickId: $clickId,
            offerId: 1,
            webId: 'web_test123',
            ip: '1.2.3.4',
            userAgent: null,
            referer: null,
            params: [],
            createdAt: $createdAt,
        );

        $ack1 = new Acknowledger(ClickBatchHandler::class, function () {});

        $message2 = new ClickMessage(
            clickId: $clickId,
            offerId: 1,
            webId: 'web_test123',
            ip: '5.6.7.8',
            userAgent: null,
            referer: null,
            params: [],
            createdAt: $createdAt,
        );

        $ack2 = new Acknowledger(ClickBatchHandler::class, function () {});

        // Вызываем process() напрямую с обоими
        $reflection = new \ReflectionMethod(ClickBatchHandler::class, 'process');
        $reflection->setAccessible(true);
        $reflection->invoke($this->handler, [[$message1, $ack1], [$message2, $ack2]]);

        $count = $this->db->fetchOne(
            'SELECT COUNT(*) FROM clicks WHERE click_id = ?',
            [$clickId]
        );

        $this->assertSame(1, (int) $count);
    }
}
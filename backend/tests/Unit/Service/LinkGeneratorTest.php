<?php
#affiliate-tracker-symfony/backend/tests/Unit/Service/LinkGeneratorTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Affiliate;
use App\Entity\Offer;
use App\Service\LinkGenerator;
use PHPUnit\Framework\TestCase;

final class LinkGeneratorTest extends TestCase
{
    private LinkGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new LinkGenerator('https://track.example.com');
    }

    public function testGenerateReturnsCorrectUrl(): void
    {
        $offer = new Offer();
        $offer->setName('Test Offer');
        $offer->setSlug('test-offer');
        $offer->setDestinationUrl('https://example.com');

        // Используем reflection, чтобы установить id (в unit-тесте нет БД)
        $reflection = new \ReflectionClass($offer);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($offer, 42);

        $affiliate = new Affiliate();
        $affiliate->setWebId('web_test123');

        $url = $this->generator->generate($offer, $affiliate);

        $this->assertSame(
            'https://track.example.com/click?offer=42&web_id=web_test123&clickid={clickid}',
            $url
        );
    }

    public function testGenerateHandlesTrailingSlashInBaseUrl(): void
    {
        $generator = new LinkGenerator('https://track.example.com/');
        $offer = new Offer();
        $reflection = new \ReflectionClass($offer);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($offer, 1);

        $affiliate = new Affiliate();
        $affiliate->setWebId('web_abc');

        $url = $generator->generate($offer, $affiliate);

        $this->assertStringStartsWith('https://track.example.com/click', $url);
    }
}
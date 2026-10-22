<?php
#affiliate-tracker-symfony/backend/src/Message/ClickMessage.php
declare(strict_types=1);

namespace App\Message;

final class ClickMessage
{
    public function __construct(
        public readonly string $clickId,
        public readonly int $offerId,
        public readonly string $webId,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly ?string $referer,
        public readonly array $params,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
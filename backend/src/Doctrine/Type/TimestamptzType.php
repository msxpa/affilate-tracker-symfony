<?php

#affiliate-tracker-symfony/backend/src/Doctrine/Type/TimestamptzType.php

declare(strict_types=1);

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;

final class TimestamptzType extends DateTimeImmutableType
{
    public const NAME = 'timestamptz';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'TIMESTAMPTZ';
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return ['timestamptz', 'timestamp with time zone'];
    }
}
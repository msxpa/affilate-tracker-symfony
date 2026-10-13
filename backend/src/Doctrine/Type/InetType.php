<?php

#affiliate-tracker-symfony/backend/src/Doctrine/Type/InetType.php

declare(strict_types=1);

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class InetType extends Type
{
    public const NAME = 'inet';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'INET';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
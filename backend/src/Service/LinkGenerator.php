<?php
#affiliate-tracker-symfony/backend/src/Service/LinkGenerator.php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Affiliate;
use App\Entity\Offer;

final class LinkGenerator
{
    public function __construct(
        private readonly string $trackBaseUrl = 'http://localhost',
    ) {
    }

    /**
     * Генерирует трекинг-ссылку для веба:
     * https://track.domain.com/click?offer=1&web_id=web_xxx&clickid={clickid}
     *
     * {clickid} — плейсхолдер, который веб заменит на свой clickid (или оставит как есть).
     */
    public function generate(Offer $offer, Affiliate $affiliate): string
    {
        return sprintf(
            '%s/click?offer=%d&web_id=%s&clickid={clickid}',
            rtrim($this->trackBaseUrl, '/'),
            $offer->getId(),
            $affiliate->getWebId(),
        );
    }
}
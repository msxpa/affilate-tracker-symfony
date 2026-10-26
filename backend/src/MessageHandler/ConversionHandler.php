<?php
#affiliate-tracker-symfony/backend/src/MessageHandler/ConversionHandler.php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ConversionMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ConversionHandler
{
    public function __invoke(ConversionMessage $message): void
    {
        // Пока пусто — конверсии записываются синхронно в postback()
        // Этот handler — для будущих async-задач (уведомления, аналитика)
    }
}
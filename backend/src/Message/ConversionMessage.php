<?php
#affiliate-tracker-symfony/backend/src/Message/ConversionMessage.php

// Зачем async: постбэк от advertiser'а должен мгновенно получить 200 OK. Запись в БД + обновление баланса — в воркере. Advertiser не должен ждать.

// ⚠️ Но! Для конверсий — осторожно. Если воркер упадёт — конверсия потеряется. Advertiser получит 200, а денег не начислится. Для финансовых операций — лучше синхронно (в транзакции) + async только для побочных эффектов (уведомления, аналитика).

// Для pet-проекта — синхронно проще и надёжнее. Async добавим позже (для статистики).

// Поэтому — используем синхронную запись в postback(), но с транзакцией.

declare(strict_types=1);

namespace App\Message;

final class ConversionMessage
{
    public function __construct(
        public readonly string $clickId,
        public readonly string $payout,
        public readonly string $status,
        public readonly array $meta,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
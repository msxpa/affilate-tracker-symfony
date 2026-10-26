<?php
#affiliate-tracker-symfony/backend/src/Service/ConversionService.php
// Транзакция — INSERT conversion + UPDATE balance атомарны. Если одно упадёт — другое откатится.
// Идемпотентность — если click_id уже есть в conversions — возвращаем существующую. 
// Advertiser может повторно отправить — не будет двойного начисления.
// bcadd — для decimal. + в PHP — float, потеря точности.
// Баланс обновляем только для approved. pending — не трогаем (ждёт подтверждения).

declare(strict_types=1);

namespace App\Service;

use App\Entity\Affiliate;
use App\Entity\Click;
use App\Entity\Conversion;
use App\Exception\ApiException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class ConversionService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $db,
    ) {
    }

    private function normalizePayout(string $payout): string
    {
        $payout = trim($payout);

        if ($payout === '' || !is_numeric($payout)) {
            throw new ApiException('Invalid payout', Response::HTTP_BAD_REQUEST);
        }

        $float = (float) $payout;

        if (!is_finite($float) || $float < 0) {
            throw new ApiException('Invalid payout', Response::HTTP_BAD_REQUEST);
        }

        if ($float > 99999999.99) {
            throw new ApiException('Payout is too large', Response::HTTP_BAD_REQUEST);
        }

        return number_format($float, 2, '.', '');
    }

    /**
     * Записывает конверсию и обновляет баланс веба (атомарно).
     *
     * @throws ApiException если клик не найден или конверсия уже существует
     */
    public function record(
        string $clickId,
        string $payout,
        string $status,
        array $meta = [],
    ): Conversion {
        // 0. Валидация входа
        $payout = $this->normalizePayout($payout);

        if (!\in_array($status, Conversion::STATUSES, true)) {
            throw new ApiException('Invalid status', Response::HTTP_BAD_REQUEST);
        }

        // 1. Ищем клик
        $click = $this->em->getRepository(Click::class)->findOneBy(['clickId' => $clickId]);

        if ($click === null) {
            throw new ApiException('Click not found', Response::HTTP_NOT_FOUND);
        }

        // 2. Проверяем, что конверсия ещё не создана (идемпотентность)
        $existing = $this->em->getRepository(Conversion::class)->findOneBy(['clickId' => $clickId]);

        if ($existing !== null) {
            // Уже обработано — возвращаем существующую (идемпотентность)
            return $existing;
        }

        // 3. Транзакция: INSERT conversion + UPDATE balance
        $this->db->beginTransaction();

        try {
            $conversion = new Conversion();
            $conversion->setClickId($clickId);
            $conversion->setOffer($click->getOffer());
            $conversion->setAffiliate($click->getAffiliate());
            $conversion->setPayout($payout);
            $conversion->setStatus($status);
            $conversion->setMeta($meta);

            $this->em->persist($conversion);

            // 4. Обновляем баланс веба (только для approved)
            if ($status === Conversion::STATUS_APPROVED) {
                $affiliate = $click->getAffiliate();
                $newBalance = bcadd($affiliate->getBalance(), $payout, 2);
                $affiliate->setBalance($newBalance);
            }

            $this->em->flush();
            $this->db->commit();

            return $conversion;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
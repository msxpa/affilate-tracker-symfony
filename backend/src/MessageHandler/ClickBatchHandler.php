<?php
#affiliate-tracker-symfony/backend/src/MessageHandler/ClickBatchHandler.php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ClickMessage;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;

#[AsMessageHandler]
final class ClickBatchHandler
{
    use BatchHandlerTrait;

    public function __construct(
        private readonly Connection $db,
    ) {
    }

    public function __invoke(ClickMessage $message, ?Acknowledger $ack = null): void
    {
        $this->handle($message, $ack);
    }

    // private function shouldFlush(): bool
    // {
    //     // return \count($this->batch) >= 500;
        
    //     //use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
    //     return $this->getBatchSize() <= \count($this->jobs);  // ← 500 <= count($jobs)
    // }

    private function getBatchSize(): int
    {
        return 500;
    }

    /**
     * @param array<int, array{0: ClickMessage, 1: Acknowledger}> $batch
     */
    private function process(array $batch): void
    {
        // debug_logx($batch);
        if ($batch === []) {
            return;
        }

        // 1. Собираем все web_id из батча
        $webIds = array_values(array_unique(array_map(
            static fn (array $pair): string => $pair[0]->webId,
            $batch,
        )));

        // 2. Загружаем affiliate_id по web_id
        $affiliateIds = $this->db->fetchAllKeyValue(
            'SELECT web_id, id FROM affiliates WHERE web_id IN (?)',
            [$webIds],
            [ArrayParameterType::STRING],
        );

        // 3. Формируем INSERT
        $values = [];
        $params = [];
        $acks = [];

        foreach ($batch as $i => [$msg, $ack]) {
            $acks[] = $ack;
            debug_logx(['$ack',$ack]);

            $affiliateId = $affiliateIds[$msg->webId] ?? null;

            if ($affiliateId === null) {
                // Веб не найден — ack всё равно (иначе retry)
                continue;
            }

            $values[] = "(:click_id_$i, :offer_id_$i, :affiliate_id_$i, :ip_$i, :ua_$i, :ref_$i, :params_$i, :created_$i)";
            debug_logx(['$values',$values]);

            $params["click_id_$i"] = $msg->clickId;
            $params["offer_id_$i"] = $msg->offerId;
            $params["affiliate_id_$i"] = $affiliateId;
            $params["ip_$i"] = $msg->ip;
            $params["ua_$i"] = $msg->userAgent !== null ? mb_substr($msg->userAgent, 0, 512) : null;
            $params["ref_$i"] = $msg->referer !== null ? mb_substr($msg->referer, 0, 512) : null;
            $params["params_$i"] = json_encode($msg->params, JSON_THROW_ON_ERROR);
            $params["created_$i"] = $msg->createdAt->format('Y-m-d H:i:s.uP');

            debug_logx(['$params',$params]);
        }

        // 4. Если нечего вставлять — просто ack все
        if ($values === []) {
            foreach ($acks as $ack) {
                $ack->ack();
            }
            return;
        }

        // 5. Один INSERT с N VALUES
        $sql = sprintf(
            'INSERT INTO clicks (click_id, offer_id, affiliate_id, ip, user_agent, referer, params, created_at)
             VALUES %s
             ON CONFLICT (click_id, created_at) DO NOTHING',
            implode(', ', $values)
        );

        $this->db->executeStatement($sql, $params);

        // 6. Ack все после успешного INSERT
        foreach ($acks as $ack) {
            $ack->ack();
        }
    }
}
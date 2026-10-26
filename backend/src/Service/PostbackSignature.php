<?php
#affiliate-tracker-symfony/backend/src/Service/PostbackSignature.php
// hash_equals — timing-safe сравнение. Обычное === уязвимо к timing-атакам.
// Порядок полей фиксирован — click_id|payout|status. Advertiser должен использовать тот же порядок.
// hash_hmac('sha256', ...) — стандарт. Не MD5, не SHA1.

declare(strict_types=1);

namespace App\Service;

final class PostbackSignature
{
    /**
     * Генерирует HMAC-SHA256 подпись для постбэка.
     */
    public function sign(string $clickId, string $payout, string $status, string $secret): string
    {
        return hash_hmac('sha256', $this->buildPayload($clickId, $payout, $status), $secret);
    }

    /**
     * Проверяет HMAC-подпись (timing-safe).
     */
    public function verify(
        string $clickId,
        string $payout,
        string $status,
        string $signature,
        string $secret,
    ): bool {
        $expected = $this->sign($clickId, $payout, $status, $secret);
        debug_logx($expected);
        return hash_equals($expected, $signature);
    }

    private function buildPayload(string $clickId, string $payout, string $status): string
    {
        return $clickId.'|'.$payout.'|'.$status;
    }
}
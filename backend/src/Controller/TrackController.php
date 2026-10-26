<?php
#affiliate-tracker-symfony/backend/src/Controller/TrackController.php
declare(strict_types=1);

namespace App\Controller;

use App\Message\ClickMessage;
use App\Service\OfferCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
// use App\Service\PostbackSignature;
use App\Service\ConversionService;
use App\Exception\ApiException;
use App\Entity\Click;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\AffiliateOffer;

final class TrackController extends AbstractController
{
    public function __construct(
        private readonly OfferCache $offerCache,
        private readonly MessageBusInterface $bus,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/postback', name: 'track_postback', methods: ['POST', 'GET'])]
    public function postback(
        Request $request,
        // PostbackSignature $signature,
        ConversionService $conversionService,
        // AffiliateOffer $affiliateOffer,
    ): Response {
        $clickId = (string) $request->query->get('click_id', '');
        // $payout = (string) $request->query->get('payout', '0');
        $payout = (string) $request->query->get('payout', '');
        $status = (string) $request->query->get('status', '');
        // $signatureValue = (string) $request->query->get('signature', '');
        $secret = (string) $request->query->get('secret', '');


        if ($clickId === '' || $payout === '' || $status === '' || $secret === '') {
            return new Response('Bad request', Response::HTTP_BAD_REQUEST);
        }

        // 1. Ищем клик и веба (для проверки)
        debug_logx($clickId);
        // $click_test = $this->em->getRepository(Click::class)->findOneBy(['click_id' => '51cf7735-d8ac-44f9-b564-86f91ea05520']);
        $click = $this->em->getRepository(Click::class)->findOneBy(['clickId' => $clickId]);

        if ($click === null) {
            return new Response('Click not found', Response::HTTP_NOT_FOUND);
        }

        $affiliate = $click->getAffiliate();
        $offer = $click->getOffer();

        $affiliateOffer = $this->em->getRepository(AffiliateOffer::class)->findOneBy([
            'affiliate' => $affiliate->getId(),
            'offer' => $offer->getId(),
        ]);

        $postbackSecret = $affiliateOffer->getPostbackSecret();

        if ($postbackSecret != $secret) {
            return new Response('Invalid postback', Response::HTTP_UNAUTHORIZED);
        }

        // 2. Проверяем HMAC
        // if (!$signature->verify($clickId, $payout, $status, $signatureValue, $affiliate->getSecret())) {
            // return new Response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        // }

        // 3. Записываем конверсию (синхронно, в транзакции)
        try {
            $conversion = $conversionService->record($clickId, $payout, $status, $request->query->all());
        } catch (ApiException $e) {
            return new Response($e->getMessage(), $e->getStatusCode());
        }

        // 4. Возвращаем 200 (advertiser доволен)
        return new Response('OK', Response::HTTP_OK);
    }

    #[Route('/click', name: 'track_click', methods: ['GET'])]
    public function click(Request $request): Response
    {
        $offerId = (int) $request->query->get('offer', 0);
        $webId = (string) $request->query->get('web_id', '');
        $clickId = (string) $request->query->get('click_id', '');

        debug_logx(["route click", $offerId, $webId, $clickId]);

        if ($offerId <= 0 || $webId === '') {
            return new Response('Bad request', Response::HTTP_BAD_REQUEST);
        }

        // ← ИСПРАВЛЕНО: проверяем и пустоту, и валидность UUID
        if ($clickId === '' || !Uuid::isValid($clickId)) {
            $clickId = Uuid::v4()->toRfc4122();
        }

        $offer = $this->offerCache->getActiveOffer($offerId);

        if ($offer === null) {
            return new Response('Offer not found or inactive', Response::HTTP_NOT_FOUND);
        }

        $this->bus->dispatch(new ClickMessage(
            clickId: $clickId,
            offerId: $offerId,
            webId: $webId,
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            referer: $request->headers->get('Referer'),
            params: $request->query->all(),
            createdAt: new \DateTimeImmutable(),
        ));

        $destinationUrl = $offer['destinationUrl'];
        $separator = str_contains($destinationUrl, '?') ? '&' : '?';

        return new RedirectResponse(
            $destinationUrl . $separator . http_build_query([
                'click_id' => $clickId,
                'web_id' => $webId,
            ]),
            Response::HTTP_FOUND,
        );
    }

}
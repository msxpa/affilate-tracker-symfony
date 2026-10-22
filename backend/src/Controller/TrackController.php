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

final class TrackController extends AbstractController
{
    public function __construct(
        private readonly OfferCache $offerCache,
        private readonly MessageBusInterface $bus,
    ) {
    }

    #[Route('/click', name: 'track_click', methods: ['GET'])]
    public function click(Request $request): Response
    {
        $offerId = (int) $request->query->get('offer', 0);
        $webId = (string) $request->query->get('web_id', '');
        $clickId = (string) $request->query->get('clickid', '');

        if ($offerId <= 0 || $webId === '') {
            return new Response('Bad request', Response::HTTP_BAD_REQUEST);
        }

        if ($clickId === '') {
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
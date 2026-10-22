<?php
#affiliate-tracker-symfony/backend/src/Controller/Api/Affilate/OfferController.php
// #[IsGranted('ROLE_AFFILIATE')] — только вебы

// denyAccessUnlessGranted('OFFER_TAKE', $offer) — проверка через OfferVoter

// GET /api/affiliate/offers — активные офферы

// POST /api/affiliate/offers/{id}/take — веб берёт оффер, получает ссылку

// GET /api/affiliate/links?offer={id} — получить ссылку на оффер

declare(strict_types=1);

namespace App\Controller\Api\Affiliate;

use App\Entity\Offer;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\OfferRepository;
use App\Service\LinkGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/affiliate', name: 'api_affiliate_')]
#[IsGranted('ROLE_AFFILIATE')]
final class OfferController extends AbstractController
{
    public function __construct(
        private readonly OfferRepository $offerRepository,
        private readonly EntityManagerInterface $em,
        private readonly LinkGenerator $linkGenerator,
    ) {
    }

    /**
     * GET /api/affiliate/offers — активные офферы
     */
    #[Route('/offers', name: 'offers_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $offers = $this->offerRepository->findBy(
            ['active' => true],
            ['id' => 'DESC'],
            100,
        );

        return new JsonResponse([
            'items' => array_map($this->serialize(...), $offers),
            'total' => \count($offers),
        ]);
    }

    /**
     * POST /api/affiliate/offers/{id}/take — взять оффер
     */
    #[Route('/offers/{id}/take', name: 'offers_take', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function take(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $offer = $this->offerRepository->find($id);

        if ($offer === null) {
            throw new ApiException('Offer not found', Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('OFFER_TAKE', $offer);

        $affiliate = $user->getAffiliate();

        if ($affiliate === null) {
            throw new ApiException('Affiliate not found', Response::HTTP_FORBIDDEN);
        }

        // Добавляем оффер вебу (M:N)
        $affiliate->addOffer($offer);
        $this->em->flush();

        return new JsonResponse([
            'offer' => $this->serialize($offer),
            'link' => $this->linkGenerator->generate($offer, $affiliate),
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/affiliate/links?offer={id} — получить ссылку на оффер
     */
    #[Route('/links', name: 'links', methods: ['GET'])]
    public function link(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $offerId = (int) $request->query->get('offer', 0);

        if ($offerId <= 0) {
            throw new ApiException('Query parameter "offer" is required', Response::HTTP_BAD_REQUEST);
        }

        $offer = $this->offerRepository->find($offerId);

        if ($offer === null) {
            throw new ApiException('Offer not found', Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('OFFER_VIEW', $offer);

        $affiliate = $user->getAffiliate();

        if ($affiliate === null) {
            throw new ApiException('Affiliate not found', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'link' => $this->linkGenerator->generate($offer, $affiliate),
        ]);
    }

    private function serialize(Offer $offer): array
    {
        return [
            'id' => $offer->getId(),
            'name' => $offer->getName(),
            'slug' => $offer->getSlug(),
            'destinationUrl' => $offer->getDestinationUrl(),
            'payout' => $offer->getPayout(),
            'revenue' => $offer->getRevenue(),
            'active' => $offer->isActive(),
            'allowedCountries' => $offer->getAllowedCountries(),
        ];
    }
}
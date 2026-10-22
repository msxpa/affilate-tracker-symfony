<?php
#affiliate-tracker-symfony/backend/src/Controller/Api/Admin/OfferController.php
declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Offer;
use App\Exception\ApiException;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/offers', name: 'api_admin_offers_')]
#[IsGranted('ROLE_ADMIN')]
final class OfferController extends AbstractController
{
    public function __construct(
        private readonly OfferRepository $offerRepository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * GET /api/admin/offers?limit=20&offset=0
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $limit = min(max(1, (int) $request->query->get('limit', 20)), 100);
        $offset = max(0, (int) $request->query->get('offset', 0));

        $offers = $this->offerRepository->findBy(
            [],
            ['id' => 'DESC'],
            $limit,
            $offset,
        );

        $total = $this->offerRepository->count([]);

        return new JsonResponse([
            'items' => array_map($this->serialize(...), $offers),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * POST /api/admin/offers
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);

        $offer = new Offer();
        $this->hydrate($offer, $data);
        $this->validate($offer);

        $this->em->persist($offer);
        $this->em->flush();

        return new JsonResponse($this->serialize($offer), Response::HTTP_CREATED);
    }

    /**
     * GET /api/admin/offers/{id}
     */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $offer = $this->offerRepository->find($id);

        if ($offer === null) {
            throw new ApiException('Offer not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serialize($offer));
    }

    /**
     * PATCH /api/admin/offers/{id}
     */
    #[Route('/{id}', name: 'update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $offer = $this->offerRepository->find($id);

        if ($offer === null) {
            throw new ApiException('Offer not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->parseJson($request);
        $this->hydrate($offer, $data);
        $this->validate($offer);

        $this->em->flush();

        return new JsonResponse($this->serialize($offer));
    }

    /**
     * DELETE /api/admin/offers/{id}
     * Soft delete — active = false
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $offer = $this->offerRepository->find($id);

        if ($offer === null) {
            throw new ApiException('Offer not found', Response::HTTP_NOT_FOUND);
        }

        $offer->setActive(false);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // --- helpers ---

    private function parseJson(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            throw new ApiException('Invalid JSON', Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    private function hydrate(Offer $offer, array $data): void
    {
        if (isset($data['name'])) {
            $offer->setName((string) $data['name']);
        }
        if (isset($data['slug'])) {
            $offer->setSlug((string) $data['slug']);
        }
        if (isset($data['destinationUrl'])) {
            $offer->setDestinationUrl((string) $data['destinationUrl']);
        }
        if (isset($data['payout'])) {
            $offer->setPayout((string) $data['payout']);
        }
        if (isset($data['revenue'])) {
            $offer->setRevenue((string) $data['revenue']);
        }
        if (isset($data['active'])) {
            $offer->setActive((bool) $data['active']);
        }
        if (isset($data['allowedCountries']) && \is_array($data['allowedCountries'])) {
            $offer->setAllowedCountries($data['allowedCountries']);
        }
    }

    private function validate(Offer $offer): void
    {
        $violations = $this->validator->validate($offer);

        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ApiException('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
        }
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
            'createdAt' => $offer->getCreatedAt()->format(\DATE_ATOM),
            'updatedAt' => $offer->getUpdatedAt()?->format(\DATE_ATOM),
        ];
    }
}
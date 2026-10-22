<?php
#affiliate-tracker-symfony/backend/src/Security/Voter/OfferVoter.php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Offer;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Offer>
 */
final class OfferVoter extends Voter
{
    public const VIEW = 'OFFER_VIEW';
    public const EDIT = 'OFFER_EDIT';
    public const DELETE = 'OFFER_DELETE';
    public const TAKE = 'OFFER_TAKE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Offer) {
            return false;
        }

        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::TAKE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Offer $offer */
        $offer = $subject;

        // Админ может всё
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        // Веб — только если оффер активен и у него есть доступ
        if ($user->hasRole(User::ROLE_AFFILIATE)) {
            $affiliate = $user->getAffiliate();

            if ($affiliate === null) {
                return false;
            }

            // Веб должен быть активен, чтобы что-то делать
            if (!$affiliate->isActive()) {
                return false;
            }

            return match ($attribute) {
                self::VIEW, self::TAKE => $offer->isActive(),
                default => false,
            };
        }

        return false;
    }
}
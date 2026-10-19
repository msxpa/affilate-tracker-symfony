<?php

#affiliate-tracker-symfony/backend/src/Controller/Api/AuthController.php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Affiliate;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * POST /api/auth/login
     * Body: { "email": "...", "password": "..." }
     * Response: { "token": "..." }
     *
     * Логика: Symfony Security сам проверяет credentials через
     * lexik_jwt_authentication.jwt_authenticator (json_login).
     * Этот метод — только фолбэк, если jwt firewall не настроен на json_login.
     * Реальная аутентификация через Lexik: POST /api/auth/login с json.
     */
    // #[Route('/login', name: 'login', methods: ['POST'])]
    // public function login(): JsonResponse
    // {
    //     // Обработка происходит в LexikJWTAuthenticationBundle через firewall
    //     // Этот метод не должен вызываться, если настроен json_login.
    //     return new JsonResponse([
    //         'error' => 'Authentication handled by JWT firewall. Provide email/password as JSON.',
    //     ], Response::HTTP_BAD_REQUEST);
    // }

    /**
     * @internal Этот метод НЕ ВЫПОЛНЯЕТСЯ. Security (json_login) перехватывает
     * запрос на этот роут ДО того, как он дойдёт до контроллера.
     * Метод существует только для регистрации роута api_login_check.
     * Не удалять! Иначе POST /api/auth/login вернёт 404.
     */
    // #[Route('/login', name: 'login', methods: ['POST'])]
    // public function login(): JsonResponse
    // {
    //     throw new \LogicException('Unreachable — Security handles authentication.');
    // }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password required'], Response::HTTP_BAD_REQUEST);
        }

        // 1. Ищем пользователя
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user === null) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Проверяем пароль
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // 3. Генерируем JWT
        $token = $jwtManager->create($user);

        // 4. Возвращаем
        return new JsonResponse(['token' => $token]);
    }



    /**
     * POST /api/auth/register
     * Body: { "email": "...", "password": "..." }
     * Response: { "user": {...}, "affiliate": {...} }
     *
     * Создаёт User с ролью ROLE_AFFILIATE и связанный Affiliate (status=pending).
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        // Валидация
        $constraints = new Assert\Collection([
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'password' => [new Assert\NotBlank(), new Assert\Length(min: 8)],
        ]);

        $violations = $this->validator->validate(
            ['email' => $email, 'password' => $password],
            $constraints
        );

        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Проверка уникальности email
        if ($this->userRepository->findOneBy(['email' => $email]) !== null) {
            return new JsonResponse(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
        }

        // Создаём User
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([User::ROLE_AFFILIATE]);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        // Создаём Affiliate (status=pending, ждёт одобрения админа)
        $affiliate = new Affiliate();
        $affiliate->setUser($user);
        $user->setAffiliate($affiliate);

        $this->em->persist($user);
        $this->em->persist($affiliate);
        $this->em->flush();

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
            'affiliate' => [
                'id' => $affiliate->getId(),
                'webId' => $affiliate->getWebId(),
                'status' => $affiliate->getStatus(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/auth/me
     * Требует JWT в заголовке Authorization: Bearer <token>
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $affiliate = $user->getAffiliate();

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
            'affiliate' => $affiliate !== null ? [
                'id' => $affiliate->getId(),
                'webId' => $affiliate->getWebId(),
                'status' => $affiliate->getStatus(),
            ] : null,
        ]);
    }
}
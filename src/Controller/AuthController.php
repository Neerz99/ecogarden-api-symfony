<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    public function __construct(private readonly string $jwtSecret)
    {
    }

    #[Route('/auth', name: 'auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $data = $this->decodeRequestBody($request);

        if (!isset($data['login']) || !is_string($data['login']) || trim($data['login']) === '') {
            throw new BadRequestHttpException('Le champ "login" est obligatoire.');
        }

        if (!isset($data['password']) || !is_string($data['password']) || $data['password'] === '') {
            throw new BadRequestHttpException('Le champ "password" est obligatoire.');
        }

        $user = $userRepository->findOneBy(['login' => trim($data['login'])]);
        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Identifiants invalides.'], 401);
        }

        $payload = [
            'sub' => $user->getLogin(),
            'roles' => $user->getRoles(),
            'iat' => time(),
            'exp' => time() + 3600 * 24,
        ];

        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

        return $this->json([
            'token' => $token,
            'expires_in' => 86400,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeRequestBody(Request $request): array
    {
        try {
            $raw = $request->getContent();
            if ($raw === '') {
                throw new BadRequestHttpException('Le corps de la requete est vide.');
            }

            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new BadRequestHttpException('Le JSON envoye est invalide.');
            }

            return $data;
        } catch (\JsonException) {
            throw new BadRequestHttpException('Le JSON envoye est invalide.');
        }
    }
}

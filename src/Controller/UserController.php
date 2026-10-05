<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user', name: 'user_create', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $this->decodeRequestBody($request);

        if (!isset($data['login']) || !is_string($data['login']) || trim($data['login']) === '') {
            throw new BadRequestHttpException('Le champ "login" est obligatoire.');
        }

        if (!isset($data['password']) || !is_string($data['password']) || $data['password'] === '') {
            throw new BadRequestHttpException('Le champ "password" est obligatoire.');
        }

        if (!isset($data['ville']) || !is_string($data['ville']) || trim($data['ville']) === '') {
            throw new BadRequestHttpException('Le champ "ville" est obligatoire.');
        }

        $login = trim($data['login']);
        if ($userRepository->findOneBy(['login' => $login])) {
            throw new BadRequestHttpException('Ce login existe deja.');
        }

        $user = new User();
        $user->setLogin($login);
        $user->setVille(trim($data['ville']));
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'ville' => $user->getVille(),
            'roles' => $user->getRoles(),
        ], 201);
    }

    #[Route('/user/{id}', name: 'user_update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $userRepository->find($id);
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        $data = $this->decodeRequestBody($request);
        if ($data === []) {
            throw new BadRequestHttpException('Aucune donnee a mettre a jour.');
        }

        $hasUpdate = false;

        if (array_key_exists('login', $data)) {
            if (!is_string($data['login']) || trim($data['login']) === '') {
                throw new BadRequestHttpException('Le champ "login" doit etre une chaine non vide.');
            }

            $newLogin = trim($data['login']);
            $existing = $userRepository->findOneBy(['login' => $newLogin]);
            if ($existing && $existing->getId() !== $user->getId()) {
                throw new BadRequestHttpException('Ce login existe deja.');
            }

            $user->setLogin($newLogin);
            $hasUpdate = true;
        }

        if (array_key_exists('password', $data)) {
            if (!is_string($data['password']) || $data['password'] === '') {
                throw new BadRequestHttpException('Le champ "password" doit etre une chaine non vide.');
            }
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
            $hasUpdate = true;
        }

        if (array_key_exists('ville', $data)) {
            if (!is_string($data['ville']) || trim($data['ville']) === '') {
                throw new BadRequestHttpException('Le champ "ville" doit etre une chaine non vide.');
            }
            $user->setVille(trim($data['ville']));
            $hasUpdate = true;
        }

        if (array_key_exists('roles', $data)) {
            if (!is_array($data['roles'])) {
                throw new BadRequestHttpException('Le champ "roles" doit etre un tableau.');
            }
            $roles = array_values(array_unique(array_filter($data['roles'], fn ($role) => is_string($role) && $role !== '')));
            if ($roles === []) {
                throw new BadRequestHttpException('Le champ "roles" ne peut pas etre vide.');
            }
            $user->setRoles($roles);
            $hasUpdate = true;
        }

        if (!$hasUpdate) {
            throw new BadRequestHttpException('Aucun champ valide fourni.');
        }

        $entityManager->flush();

        return $this->json([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'ville' => $user->getVille(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/user/{id}', name: 'user_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur supprime avec succes.']);
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

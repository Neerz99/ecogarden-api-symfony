<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Repository\ConseilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/conseil')]
class ConseilController extends AbstractController
{
    /**
     * GET /conseil/ - retourne tous les conseils du mois en cours.
     */
    #[Route('/', name: 'conseil_list_current', methods: ['GET'])]
    public function listCurrent(ConseilRepository $conseilRepository): JsonResponse
    {
        $mois = (int) date('n'); // mois courant (1-12)

        $conseils = $conseilRepository->findByMois($mois);

        return $this->json(array_map(fn($c) => [
            'id'     => $c->getId(),
            'contenu' => $c->getContenu(),
            'mois'    => $c->getMois(),
        ], $conseils));
    }

    /**
     * GET /conseil/{mois} - retourne tous les conseils du mois spécifié.
     */
    #[Route('/{mois}', name: 'conseil_list_by_mois', requirements: ['mois' => '\d+'], methods: ['GET'])]
    public function listByMois(int $mois, ConseilRepository $conseilRepository): JsonResponse
    {
        if ($mois < 1 || $mois > 12) {
            return $this->json(['error' => 'Mois invalide. Utilisez une valeur entre 1 (janvier) et 12 (décembre).'], 400);
        }

        $conseils = $conseilRepository->findByMois($mois);

        return $this->json(array_map(fn($c) => [
            'id'      => $c->getId(),
            'contenu' => $c->getContenu(),
            'mois'    => $c->getMois(),
        ], $conseils));
    }

    /**
     * POST /conseil - ajoute un nouveau conseil (admin uniquement).
     */
    #[Route('', name: 'conseil_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeRequestBody($request);

        if (!isset($data['contenu']) || !is_string($data['contenu']) || trim($data['contenu']) == '') {
            throw new BadRequestHttpException('Le champ "contenu" est obligatoire.');
        }

        if (!isset($data['mois']) || !is_array($data['mois'])) {
            throw new BadRequestHttpException('Le champ "mois" est obligatoire et doit etre un tableau.');
        }

        $mois = $this->validateMoisList($data['mois']);

        $conseil = new Conseil();
        $conseil->setContenu(trim($data['contenu']));
        $conseil->setMois($mois);

        $entityManager->persist($conseil);
        $entityManager->flush();

        return $this->json([
            'id' => $conseil->getId(),
            'contenu' => $conseil->getContenu(),
            'mois' => $conseil->getMois(),
        ], 201);
    }

    /**
     * PUT /conseil/{id} - met a jour partiellement un conseil (admin uniquement).
     */
    #[Route('/{id}', name: 'conseil_update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request, ConseilRepository $conseilRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $conseil = $conseilRepository->find($id);
        if (!$conseil) {
            throw new NotFoundHttpException('Conseil introuvable.');
        }

        $data = $this->decodeRequestBody($request);
        if ($data === []) {
            throw new BadRequestHttpException('Aucune donnee a mettre a jour.');
        }

        $hasUpdate = false;

        if (array_key_exists('contenu', $data)) {
            if (!is_string($data['contenu']) || trim($data['contenu']) == '') {
                throw new BadRequestHttpException('Le champ "contenu" doit etre une chaine non vide.');
            }
            $conseil->setContenu(trim($data['contenu']));
            $hasUpdate = true;
        }

        if (array_key_exists('mois', $data)) {
            if (!is_array($data['mois'])) {
                throw new BadRequestHttpException('Le champ "mois" doit etre un tableau.');
            }
            $conseil->setMois($this->validateMoisList($data['mois']));
            $hasUpdate = true;
        }

        if (!$hasUpdate) {
            throw new BadRequestHttpException('Aucun champ valide fourni.');
        }

        $entityManager->flush();

        return $this->json([
            'id' => $conseil->getId(),
            'contenu' => $conseil->getContenu(),
            'mois' => $conseil->getMois(),
        ]);
    }

    /**
     * DELETE /conseil/{id} - supprime un conseil (admin uniquement).
     */
    #[Route('/{id}', name: 'conseil_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function delete(int $id, ConseilRepository $conseilRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $conseil = $conseilRepository->find($id);
        if (!$conseil) {
            throw new NotFoundHttpException('Conseil introuvable.');
        }

        $entityManager->remove($conseil);
        $entityManager->flush();

        return $this->json(['message' => 'Conseil supprime avec succes.']);
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

    /**
     * @param mixed[] $mois
     * @return int[]
     */
    private function validateMoisList(array $mois): array
    {
        if ($mois === []) {
            throw new BadRequestHttpException('La liste des mois ne peut pas etre vide.');
        }

        $normalized = [];
        foreach ($mois as $m) {
            if (!is_int($m) && !(is_string($m) && ctype_digit($m))) {
                throw new BadRequestHttpException('Chaque mois doit etre un entier entre 1 et 12.');
            }

            $value = (int) $m;
            if ($value < 1 || $value > 12) {
                throw new BadRequestHttpException('Chaque mois doit etre compris entre 1 et 12.');
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }
}

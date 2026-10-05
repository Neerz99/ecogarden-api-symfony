<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conseil>
 */
class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    /**
     * Retourne tous les conseils qui s'appliquent au mois donné.
     *
     * @return Conseil[]
     */
    public function findByMois(int $mois): array
    {
        $all = $this->findAll();

        return array_values(array_filter($all, fn(Conseil $c) => in_array($mois, $c->getMois(), true)));
    }
}

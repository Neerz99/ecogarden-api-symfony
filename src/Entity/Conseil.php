<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Contenu textuel du conseil.
     */
    #[ORM\Column(type: 'text')]
    private ?string $contenu = null;

    /**
     * Liste des mois où le conseil s'applique (1 = janvier, ..., 12 = décembre).
     *
     * @var list<int>
     */
    #[ORM\Column(type: 'json')]
    private array $mois = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    /**
     * @return list<int>
     */
    public function getMois(): array
    {
        return $this->mois;
    }

    /**
     * @param list<int> $mois
     */
    public function setMois(array $mois): static
    {
        $this->mois = $mois;

        return $this;
    }
}

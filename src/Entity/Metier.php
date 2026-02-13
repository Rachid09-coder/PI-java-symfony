<?php

namespace App\Entity;

use App\Repository\MetierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierRepository::class)]
class Metier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'metier', targetEntity: MetierAvance::class, orphanRemoval: true)]
    private Collection $metierAvances;

    public function __construct()
    {
        $this->metierAvances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, MetierAvance>
     */
    public function getMetierAvances(): Collection
    {
        return $this->metierAvances;
    }

    public function addMetierAvance(MetierAvance $metierAvance): static
    {
        if (!$this->metierAvances->contains($metierAvance)) {
            $this->metierAvances->add($metierAvance);
            $metierAvance->setMetier($this);
        }

        return $this;
    }

    public function removeMetierAvance(MetierAvance $metierAvance): static
    {
        if ($this->metierAvances->removeElement($metierAvance)) {
            // set the owning side to null (unless already changed)
            if ($metierAvance->getMetier() === $this) {
                $metierAvance->setMetier(null);
            }
        }

        return $this;
    }
}

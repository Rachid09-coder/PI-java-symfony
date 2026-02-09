<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $role = null; // etudiant | professeur

    #[ORM\Column(length: 200)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $numtel = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    // ✅ obligatoire pour PasswordAuthenticatedUserInterface
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getNumtel(): ?string { return $this->numtel; }
    public function setNumtel(string $numtel): static { $this->numtel = $numtel; return $this; }

    // ✅ obligatoire pour UserInterface (Symfony 6)
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    // ✅ Symfony veut un tableau de rôles
    public function getRoles(): array
    {
        // On mappe ton champ string -> format Symfony
        if ($this->role === 'professeur') {
            return ['ROLE_PROFESSEUR'];
        }

        return ['ROLE_ETUDIANT'];
    }

    public function eraseCredentials(): void
    {
        // rien à faire ici
    }
}

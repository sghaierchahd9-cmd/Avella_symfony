<?php

namespace App\Entity;

use App\Repository\ProduitCouleurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitCouleurRepository::class)]
#[ORM\Table(name: 'produit_couleur')]
#[ORM\UniqueConstraint(columns: ['produit_id', 'couleur_id'])]
class ProduitCouleur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(targetEntity: Couleur::class)]
    #[ORM\JoinColumn(name: 'couleur_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Couleur $couleur = null;

    #[ORM\Column(length: 512)]
    private ?string $image = null;

    public function getId(): ?int { return $this->id; }

    public function getProduit(): ?Produit { return $this->produit; }
    public function setProduit(?Produit $produit): self { $this->produit = $produit; return $this; }

    public function getCouleur(): ?Couleur { return $this->couleur; }
    public function setCouleur(?Couleur $couleur): self { $this->couleur = $couleur; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(string $image): self { $this->image = $image; return $this; }
}
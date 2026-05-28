<?php

namespace App\Repository;

use App\Entity\ProduitCouleur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitCouleurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProduitCouleur::class);
    }
    //bech ta3tyh un produit bech yel9alk les couleurs (necessaire lel profile de boutique)
    public function findByProduit(Produit $produit): array
    {
        return $this->createQueryBuilder('pc')
            ->select('pc', 'c')
            ->join('pc.couleur', 'c')
            ->where('pc.produit = :produit')
            ->setParameter('produit', $produit)
            ->getQuery()
            ->getResult();
    }
}
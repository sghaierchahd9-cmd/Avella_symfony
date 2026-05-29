<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function searchAll(string $nom = '', string $categorie = '', string $boutique = ''): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->leftJoin('p.boutique', 'b')
            ->addSelect('c', 'b');

        if ($nom !== '') {
            $qb->andWhere('p.nom LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        if ($categorie !== '') {
            $qb->andWhere('c.nom LIKE :categorie')
                ->setParameter('categorie', '%' . $categorie . '%');
        }

        if ($boutique !== '') {
            $qb->andWhere('b.nom LIKE :boutique')
                ->setParameter('boutique', '%' . $boutique . '%');
        }

        return $qb->getQuery()->getResult();
    }
    public function findTendances(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.boutique', 'b')
            ->addSelect('b')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->where("b.statut = 'actif'")
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function findById(int $id): ?Produit
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.boutique', 'b')
            ->addSelect('b')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
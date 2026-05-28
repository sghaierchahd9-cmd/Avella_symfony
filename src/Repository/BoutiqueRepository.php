<?php

namespace App\Repository;

use App\Entity\Boutique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Boutique>
 */
class BoutiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boutique::class);
    }

    //    /**
    //     * @return Boutique[] Returns an array of Boutique objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Boutique
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.produits', 'p')
            ->addSelect('COUNT(p.id) AS HIDDEN nb_produits') // HIDDEN = pas dans le résultat
            ->join('b.categorie', 'c')                      // eager load la catégorie
            ->addSelect('c')
            ->where('b.statut = :statut')
            ->setParameter('statut', 'actif')
            ->groupBy('b.id')
            ->orderBy('b.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findByCategorie(int $categorieId): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.categorie', 'c')
            ->addSelect('c')
            ->where('b.categorie = :cat')
            ->andWhere('b.statut = :statut')
            ->setParameter('cat', $categorieId)
            ->setParameter('statut', 'actif')
            ->orderBy('b.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // remplace findByName()
    public function findByName(string $search): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.categorie', 'c')
            ->addSelect('c')
            ->where('b.nom LIKE :search')
            ->andWhere('b.statut = :statut')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('statut', 'actif')
            ->orderBy('b.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

}

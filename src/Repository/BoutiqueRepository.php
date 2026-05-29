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
    public function findAllActivePaginated(int $limit = 6, int $offset = 0): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.categorie', 'c')
            ->addSelect('c')
            ->where("b.statut = 'actif'")
            ->orderBy('b.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where("b.statut = 'actif'")
            ->getQuery()
            ->getSingleScalarResult();
    }
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.categorie', 'c')
            ->addSelect('c')
            ->where("b.statut = 'actif'")
            ->orderBy('b.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findByCategorie(int $categorieId): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.categorie', 'c')
            ->addSelect('c')
            ->where('b.categorie = :categorieId')
            ->andWhere("b.statut = 'actif'")
            ->setParameter('categorieId', $categorieId)
            ->orderBy('b.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $search): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.categorie', 'c')
            ->addSelect('c')
            ->where('b.nom LIKE :search')
            ->andWhere("b.statut = 'actif'")
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('b.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findOneBy(array $criteria, array $orderBy = null): ?Boutique
    {
        return parent::findOneBy($criteria, $orderBy);
    }
    public function findPaginated(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $results = $this->createQueryBuilder('b')
            ->leftJoin('b.categorie', 'c')
            ->addSelect('c')
            ->where("b.statut = 'actif'")
            ->orderBy('b.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        $total = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where("b.statut = 'actif'")
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'results' => $results,
            'total'   => (int) $total,
        ];
    }
    public function findByCategoriePaginated(int $categorieId, int $limit = 6, int $offset = 0): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.categorie', 'c')
            ->addSelect('c')
            ->where('b.categorie = :categorieId')
            ->andWhere("b.statut = 'actif'")
            ->setParameter('categorieId', $categorieId)
            ->orderBy('b.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByCategorie(int $categorieId): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.categorie = :categorieId')
            ->andWhere("b.statut = 'actif'")
            ->setParameter('categorieId', $categorieId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByNamePaginated(string $search, int $limit = 6, int $offset = 0): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.categorie', 'c')
            ->addSelect('c')
            ->where('b.nom LIKE :search')
            ->andWhere("b.statut = 'actif'")
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('b.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByName(string $search): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.nom LIKE :search')
            ->andWhere("b.statut = 'actif'")
            ->setParameter('search', '%' . $search . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

}

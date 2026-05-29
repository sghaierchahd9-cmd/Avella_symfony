<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findPendingByUser(User $user): ?Commande
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('user',   $user)
            ->setParameter('statut', 'en_attente')
            ->orderBy('c.created_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function countCartItems(User $user): int
    {
        $commande = $this->findPendingByUser($user);
        if ($commande === null) {
            return 0;
        }

        $result = $this->getEntityManager()
            ->createQuery(
                'SELECT COALESCE(SUM(cp.quantite), 0)
                 FROM App\Entity\CommandeProduits cp
                 WHERE cp.commande = :commande'
            )
            ->setParameter('commande', $commande)
            ->getSingleScalarResult();

        return (int) $result;
    }
    public function findOrderHistoryByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statut != :statut')
            ->setParameter('user',   $user)
            ->setParameter('statut', 'en_attente')
            ->orderBy('c.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

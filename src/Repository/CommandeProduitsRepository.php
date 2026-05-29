<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\CommandeProduits;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeProduits>
 */
class CommandeProduitsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeProduits::class);
    }

    /**
     * @return CommandeProduits[]
     */
    public function findByCommande(Commande $commande): array
    {
        return $this->createQueryBuilder('cp')
            ->addSelect('p', 'b')
            ->join('cp.produit', 'p')
            ->leftJoin('p.boutique', 'b')
            ->andWhere('cp.commande = :commande')
            ->setParameter('commande', $commande)
            ->orderBy('cp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByIdAndPendingUser(int $id, User $user): ?CommandeProduits
    {
        return $this->createQueryBuilder('cp')
            ->addSelect('c', 'p')
            ->join('cp.commande', 'c')
            ->join('cp.produit', 'p')
            ->andWhere('cp.id = :id')
            ->andWhere('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->setParameter('statut', 'en_attente')
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return CommandeProduits[] Returns an array of CommandeProduits objects
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

    //    public function findOneBySomeField($value): ?CommandeProduits
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

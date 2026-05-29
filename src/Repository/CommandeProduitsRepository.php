<?php

namespace App\Repository;

use App\Entity\CommandeProduits;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Commande;
use App\Entity\User;


/**
 * @extends ServiceEntityRepository<CommandeProduits>
 */
class CommandeProduitsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeProduits::class);
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
    public function findByCommande(Commande $commande): array
    {
        return $this->createQueryBuilder('cp')
            ->join('cp.produit', 'p')
            ->addSelect('p')
            ->join('p.boutique', 'b')
            ->addSelect('b')
            ->where('cp.commande = :commande')
            ->setParameter('commande', $commande)
            ->orderBy('cp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findByIdAndPendingUser(int $itemId, User $user): ?CommandeProduits
    {
        return $this->createQueryBuilder('cp')
            ->join('cp.commande', 'c')
            ->where('cp.id = :itemId')
            ->andWhere('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('itemId', $itemId)
            ->setParameter('user',   $user)
            ->setParameter('statut', 'en_attente')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

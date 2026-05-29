<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\CommandeProduits;
use App\Entity\Produit;
use App\Entity\User;
use App\Repository\CommandeProduitsRepository;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * CartService
 *
 * Encapsulates all cart / commande logic originally spread across
 * commandes.php (PHP class) and the various add-to-cart / update-cart /
 * cancel-cart / confirm_order pages.
 *
 * Business rules (unchanged from original):
 *  - ONE 'en_attente' commande per user = the active cart.
 *  - Items live in commande_produits.
 *  - Confirming a cart sets statut = 'confirmee'.
 *  - The cart total is always recomputed from produit.prix × quantite.
 */
class CartService
{
    private $em;
    private $commandeRepo;
    private $cpRepo;
    private $produitRepo;

    public function __construct(
        EntityManagerInterface             $em,
        CommandeRepository                 $commandeRepo,
        CommandeProduitsRepository $cpRepo,
        ProduitRepository          $produitRepo,
    ) {
        $this->produitRepo = $produitRepo;
        $this->cpRepo = $cpRepo;
        $this->commandeRepo = $commandeRepo;
        $this->em = $em;
    }

    // -------------------------------------------------------------------------
    // Core helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the current pending commande for the user,
     * creating a fresh one when none exists yet.
     * Mirrors commandes::getOrCreateCart().
     */
    public function getOrCreateCart(User $user): Commande
    {
        $commande = $this->commandeRepo->findPendingByUser($user);

        if ($commande === null) {
            $commande = new Commande();
            $commande->setUser($user);
            $commande->setTotal('0.000');
            $commande->setStatut('en_attente');
            $commande->setCreatedAt(new \DateTimeImmutable());
            $commande->setUpdatedAt(new \DateTime());
            $this->em->persist($commande);
            $this->em->flush();
        }

        return $commande;
    }

    /**
     * Returns all line-items for the given commande.
     * Mirrors commandes::getCartItems().
     *
     * @return CommandeProduits[]
     */
    public function getCartItems(Commande $commande): array
    {
        return $this->cpRepo->findByCommande($commande);
    }

    /**
     * Returns [commande, items, total] for the current user's cart.
     * Mirrors commandes::getOrCreateCart() return value.
     */
    public function getCartData(User $user): array
    {
        $commande = $this->getOrCreateCart($user);
        $items    = $this->getCartItems($commande);
        $total    = array_reduce(
            $items,
            fn(float $carry, CommandeProduits $cp) =>
                $carry + ((float)$cp->getProduit()->getPrix() * $cp->getQuantite()),
            0.0
        );

        return [
            'commande' => $commande,
            'items'    => $items,
            'total'    => $total,
        ];
    }

    /**
     * Counts total product units in the user's cart (used for the navbar badge).
     * Returns 0 when no cart exists.
     * Mirrors commandes::countCartItems().
     */
    public function countCartItems(User $user): int
    {
        return $this->commandeRepo->countCartItems($user);
    }

    // -------------------------------------------------------------------------
    // Mutations
    // -------------------------------------------------------------------------

    /**
     * Adds $quantite units of $produitId to the user's cart.
     * If the product is already in the cart, the quantity is incremented.
     * Returns the new total item count in the cart.
     * Mirrors commandes::addItem().
     */
    public function addItem(User $user, int $produitId, int $quantite = 1): int
    {
        $commande = $this->getOrCreateCart($user);
        $produit  = $this->produitRepo->find($produitId);

        if ($produit === null) {
            return $this->countItemsInCommande($commande);
        }

        // Check for an existing line-item
        $existing = $this->cpRepo->findOneBy([
            'commande' => $commande,
            'produit'  => $produit,
        ]);

        if ($existing !== null) {
            $existing->setQuantite($existing->getQuantite() + $quantite);
        } else {
            $cp = new CommandeProduits();
            $cp->setCommande($commande);
            $cp->setProduit($produit);
            $cp->setQuantite($quantite);
            $this->em->persist($cp);
        }

        $this->em->flush();
        $this->recalcTotal($commande);

        return $this->countItemsInCommande($commande);
    }

    /**
     * Sets the quantity of line-item $itemId to $quantite.
     * If $quantite <= 0 the item is deleted.
     * Returns the new total item count, or -1 when the item does not exist /
     * does not belong to this user's pending cart.
     * Mirrors commandes::updateItem() — original behavior preserved exactly,
     * including the dead-variable issue (silently dropped; runtime is identical).
     */
    public function updateItem(User $user, int $itemId, int $quantite): int
    {
        $item = $this->cpRepo->findByIdAndPendingUser($itemId, $user);

        if ($item === null) {
            return -1;
        }

        $commande = $item->getCommande();

        if ($quantite <= 0) {
            $this->em->remove($item);
        } else {
            $item->setQuantite($quantite);
        }

        $this->em->flush();
        $this->recalcTotal($commande);

        return $this->countItemsInCommande($commande);
    }

    /**
     * Cancels (empties) the user's current pending cart.
     * Deletes all line-items and the commande itself, then returns true.
     * Returns false when no pending cart exists.
     * Mirrors add-to-cart / cancel-cart.php.
     */
    public function cancelCart(User $user): bool
    {
        $commande = $this->commandeRepo->findPendingByUser($user);

        if ($commande === null) {
            return false;
        }

        foreach ($this->getCartItems($commande) as $item) {
            $this->em->remove($item);
        }

        $this->em->remove($commande);
        $this->em->flush();

        return true;
    }

    /**
     * Confirms the user's pending cart (sets statut = 'confirmee').
     * Returns false when no pending cart exists or when it is empty.
     * Mirrors commandes::confirmOrder().
     */
    public function confirmOrder(User $user): bool
    {
        $commande = $this->commandeRepo->findPendingByUser($user);

        if ($commande === null) {
            return false;
        }

        $items = $this->getCartItems($commande);

        if (empty($items)) {
            return false;
        }

        $this->recalcTotal($commande);

        $commande->setStatut('confirmee');
        $commande->setUpdatedAt(new \DateTime());
        $this->em->flush();

        return true;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Recomputes the commande total from the current line-items via a DQL
     * aggregate query, then persists the result.
     * Mirrors the UPDATE … SET total = (SELECT SUM …) in commandes::recalcTotal().
     */
    private function recalcTotal(Commande $commande): void
    {
        $total = $this->em
            ->createQuery(
                'SELECT COALESCE(SUM(cp.quantite * p.prix), 0)
                 FROM App\Entity\CommandeProduits cp
                 JOIN cp.produit p
                 WHERE cp.commande = :commande'
            )
            ->setParameter('commande', $commande)
            ->getSingleScalarResult();

        $commande->setTotal((string) $total);
        $commande->setUpdatedAt(new \DateTime());
        $this->em->flush();
    }

    /**
     * Returns the total number of product units in a commande.
     * Mirrors commandes::countItems().
     */
    private function countItemsInCommande(Commande $commande): int
    {
        $result = $this->em
            ->createQuery(
                'SELECT COALESCE(SUM(cp.quantite), 0)
                 FROM App\Entity\CommandeProduits cp
                 WHERE cp.commande = :commande'
            )
            ->setParameter('commande', $commande)
            ->getSingleScalarResult();

        return (int) $result;
    }
}

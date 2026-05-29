<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CartController
 *
 * Handles all cart interactions:
 *  - AJAX endpoints: ajouter, modifier, vider, getCart
 *  - HTML fragment:  fragment  (refreshed by cart.js via AJAX)
 *  - Full page:      confirmer (POST → redirect)
 *
 * Unauthenticated AJAX requests are caught by the Symfony firewall; if the
 * user reaches an endpoint without a session the access_control rules in
 * security.yaml redirect them to /login.  For AJAX callers that bypass this
 * (e.g. expired session mid-page), the JSON responses include a 'redirect'
 * key matching the original PHP behavior.
 */
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    // -------------------------------------------------------------------------
    // AJAX — add to cart
    // Mirrors: pages/buyer/add-to-cart.php
    // -------------------------------------------------------------------------

    #[Route('/buyer/add-to-cart', name: 'cart_ajouter', methods: ['POST'])]
    public function ajouter(Request $request, CartService $cartService): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user      = $this->getUser();
        $produitId = (int) $request->request->get('produit_id', 0);
        $quantite  = max(1, (int) $request->request->get('quantite', 1));

        if ($produitId <= 0) {
            return $this->json(['success' => false, 'message' => 'Produit invalide.']);
        }

        $count = $cartService->addItem($user, $produitId, $quantite);

        return $this->json([
            'success'    => true,
            'cart_count' => $count,
        ]);
    }

    // -------------------------------------------------------------------------
    // AJAX — update cart item quantity
    // Mirrors: pages/buyer/update-cart.php
    // -------------------------------------------------------------------------

    #[Route('/buyer/update-cart', name: 'cart_modifier', methods: ['POST'])]
    public function modifier(Request $request, CartService $cartService): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $itemId   = (int) ($request->request->get('item_id') ?? $request->request->get('cp_id', 0));
        $action   = (string) $request->request->get('action', '');
        $quantite = (int) $request->request->get('quantite', 1);

        if ($itemId <= 0) {
            return $this->json(['success' => false, 'message' => 'Item invalide.']);
        }

        if ($action !== '') {
            $cartData = $cartService->getCartData($user);
            $item = null;

            foreach ($cartData['items'] as $cartItem) {
                if ($cartItem->getId() === $itemId) {
                    $item = $cartItem;
                    break;
                }
            }

            if ($item === null) {
                return $this->json(['success' => false, 'message' => 'Item introuvable.']);
            }

            $currentQty = (int) $item->getQuantite();
            $quantite = match ($action) {
                'plus', 'increment' => $currentQty + 1,
                'minus', 'decrement' => $currentQty - 1,
                'remove' => 0,
                default => $quantite,
            };
        }

        $count = $cartService->updateItem($user, $itemId, $quantite);

        if ($count === -1) {
            return $this->json(['success' => false, 'message' => 'Item introuvable.']);
        }

        $cartData = $cartService->getCartData($user);
        $updatedItem = null;

        foreach ($cartData['items'] as $cartItem) {
            if ($cartItem->getId() === $itemId) {
                $updatedItem = $cartItem;
                break;
            }
        }

        return $this->json([
            'success'    => true,
            'cart_count' => $count,
            'cart_empty' => count($cartData['items']) === 0,
            'cart_total' => number_format((float) $cartData['total'], 2, '.', ''),
            'removed'    => $updatedItem === null,
            'item_qty'   => $updatedItem?->getQuantite(),
            'item_sub'   => $updatedItem
                ? number_format((float) $updatedItem->getProduit()->getPrix() * $updatedItem->getQuantite(), 2, '.', '')
                : '0.00',
        ]);
    }

    // -------------------------------------------------------------------------
    // AJAX — cancel (empty) the cart
    // Mirrors: pages/buyer/cancel-cart.php
    // -------------------------------------------------------------------------

    #[Route('/buyer/cancel-cart', name: 'cart_vider', methods: ['POST'])]
    public function vider(CartService $cartService): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $cartService->cancelCart($user);

        return $this->json([
            'success'    => true,
            'cart_count' => 0,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST — confirm order (form submit, not AJAX)
    // Mirrors: pages/buyer/confirm_order.php
    // -------------------------------------------------------------------------

    #[Route('/buyer/confirm-order', name: 'cart_confirmer', methods: ['POST'])]
    public function confirmer(CartService $cartService): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $success = $cartService->confirmOrder($user);

        if (!$success) {
            $this->addFlash('error', 'Votre panier est vide ou introuvable.');
            return $this->redirectToRoute('buyer_dashboard');
        }

        $this->addFlash('success', 'Votre commande a bien été confirmée !');
        return $this->redirectToRoute('buyer_dashboard');
    }

    // -------------------------------------------------------------------------
    // GET — HTML cart fragment (AJAX, returns partial HTML)
    // Mirrors: pages/buyer/cart.php?cart_fragment=1
    // -------------------------------------------------------------------------

    #[Route('/buyer/cart', name: 'cart_fragment', methods: ['GET'])]
    public function fragment(CartService $cartService): Response
    {
        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $cartData = $cartService->getCartData($user);

        return $this->render('components/_cart_fragment.html.twig', [
            'items' => $cartData['items'],
            'total' => $cartData['total'],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET — full cart JSON payload
    // Mirrors: pages/buyer/get-cart.php
    // -------------------------------------------------------------------------

    #[Route('/buyer/get-cart', name: 'cart_get', methods: ['GET'])]
    public function getCart(CartService $cartService): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $cartData = $cartService->getCartData($user);

        $items = array_map(static function ($cp) {
            $produit = $cp->getProduit();
            return [
                'item_id'       => $cp->getId(),
                'produit_id'    => $produit->getId(),
                'produit_nom'   => $produit->getNom(),
                'prix_unitaire' => $produit->getPrix(),
                'produit_image' => $produit->getImage(),
                'boutique_nom'  => $produit->getBoutique()?->getNom(),
                'quantite'      => $cp->getQuantite(),
                'sous_total'    => (float) $produit->getPrix() * $cp->getQuantite(),
            ];
        }, $cartData['items']);

        return $this->json([
            'success'      => true,
            'commande_id'  => $cartData['commande']->getId(),
            'items'        => $items,
            'total'        => $cartData['total'],
            'cart_count'   => array_sum(array_column($items, 'quantite')),
        ]);
    }
}

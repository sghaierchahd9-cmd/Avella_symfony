<?php

namespace App\Controller;

use App\Repository\BoutiqueRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class BuyerController extends AbstractController
{
    #[Route('/buyer', name: 'buyer_dashboard', methods: ['GET'])]
    public function dashboard(
        CategorieRepository $categorieRepo,
        ProduitRepository   $produitRepo,
        BoutiqueRepository  $boutiqueRepo,
        CartService         $cartService,
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $categories = $categorieRepo->findAllOrderedByNom();
        $tendances  = $produitRepo->findTendances();
        $boutiques  = $boutiqueRepo->findBy(['statut' => 'actif'], ['created_at' => 'DESC']);

        $cartCount = $user ? $cartService->countCartItems($user) : 0;

        return $this->render('buyer/dashboard.html.twig', [
            'categories' => $categories,
            'tendances'  => $tendances,
            'boutiques'  => $boutiques,
            'cartCount'  => $cartCount,
        ]);
    }}
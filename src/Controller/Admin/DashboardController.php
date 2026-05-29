<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        CategorieRepository $categorieRepository,
        BoutiqueRepository $boutiqueRepository,
        ProduitRepository $produitRepository,
        CommandeRepository $commandeRepository,
    ): Response {
        $recentOrders = $commandeRepository->findBy([], ['created_at' => 'DESC'], 5);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'users' => $userRepository->count([]),
                'categories' => $categorieRepository->count([]),
                'boutiques' => $boutiqueRepository->count([]),
                'products' => $produitRepository->count([]),
                'orders' => $commandeRepository->count([]),
                'pendingOrders' => $commandeRepository->count(['statut' => 'en_attente']),
                'confirmedOrders' => $commandeRepository->count(['statut' => 'confirmee']),
            ],
            'recentOrders' => $recentOrders,
        ]);
    }
}

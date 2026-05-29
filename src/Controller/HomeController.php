<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(
        CategorieRepository $categorieRepo,
        ProduitRepository   $produitRepo,
    ): Response {
        $categories = $categorieRepo->findAll();
        $tendances  = $produitRepo->findTendances(8);

        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'tendances'  => $tendances,
            'cartCount'  => 0,
        ]);
    }
}

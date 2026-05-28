<?php

namespace App\Controller;


use App\Repository\BoutiqueRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
final class BoutiqueController extends AbstractController
{
   // main pages (listes et filtrage )
    #[Route('/boutiques', name: 'app_boutique')]
    public function boutiques(CategorieRepository $categorieRepo): Response
    {
        $categories = $categorieRepo->findAll();

        return $this->render('boutique/listeBoutiques.html.twig', [
            'categories' => $categories,
        ]);
    }
//rechercher et filtrage

    /**
     * @param Request $request
     * @param BoutiqueRepository $boutiqueRepo
     * @return JsonResponse
     */
    #[Route('/boutiques/recherche', name: 'app_boutique_search')]
    public function recherche(
        Request           $request,
        BoutiqueRepository $boutiqueRepo
    ): JsonResponse {

        $search      = trim($request->query->get('search', ''));
        $categorieId = (int) $request->query->get('categorie_id', 0);

        // ─ même logique que search_boutiques.php ─
        if ($categorieId > 0 && $search !== '') {
            $boutiques   = $boutiqueRepo->findByCategorie($categorieId);
            $searchLower = mb_strtolower($search);
            $boutiques   = array_values(array_filter(
                $boutiques,
                fn($b) => str_contains(mb_strtolower($b->getNom()), $searchLower)
            ));

        } elseif ($categorieId > 0) {
            $boutiques = $boutiqueRepo->findByCategorie($categorieId);

        } elseif ($search !== '') {
            $boutiques = $boutiqueRepo->findByName($search);

        } else {
            $boutiques = $boutiqueRepo->findAllActive();
        }

        // ─ sérialisation manuelle
        $result = array_map(fn($b) => [
            'id'            => $b->getId(),
            'nom'           => $b->getNom(),
            'description'   => $b->getDescription() ?? '',
            'photo'         => $b->getPhoto() ?? '',
            'categorie_nom' => $b->getCategorie()?->getNom() ?? '',
        ], $boutiques);

        return $this->json([
            'success'   => true,
            'boutiques' => $result,
            'count'     => count($result),
        ]);
    }
}

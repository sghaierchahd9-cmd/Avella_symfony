<?php

namespace App\Controller;


use App\Repository\BoutiqueRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProduitCouleurRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Boutique;
final class BoutiqueController extends AbstractController
{
   // main pages (listes et filtrage )
    #[Route('/boutiques', name: 'app_boutique')]
    public function boutiques(CategorieRepository $categorieRepo,BoutiqueRepository $boutiqueRepo): Response
    {
        $categories = $categorieRepo->findAll();
        $boutiques = $boutiqueRepo->findAllActive();

        return $this->render('boutique/listeBoutiques.html.twig', [
            'categories' => $categories,
            'boutiques' => $boutiques
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
            $boutiques = $boutiqueRepo->findAll();

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
    #[Route('/seller/boutique/enregistrer', name: 'seller_boutique_enregistrer', methods: ['POST'])]
    public function enregistrerBoutique(
        Request $request,
        BoutiqueRepository $boutiqueRepo,
        CategorieRepository $categorieRepo,
        \Doctrine\ORM\EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 1. Vérifier si ce vendeur a déjà une boutique
        $boutique = $boutiqueRepo->findOneBy(['user' => $user]);
        $isNew = false;

        if (!$boutique) {
            // Mode CRÉATION
            $boutique = new Boutique();
            $boutique->setUserId($user); // Nom exact de la méthode dans votre Boutique.php
            $boutique->setCreatedAt(new \DateTime());
            $boutique->setStatut('actif');
            $isNew = true;
        } else {
            // Mode MODIFICATION
            $boutique->setUpdatedAt(new \DateTime());
        }

        // Récupération des données brutes du formulaire HTML natif
        $data = $request->request->all('boutique_form');
        $boutique->setNom($data['nom'] ?? '');
        $boutique->setDescription($data['description'] ?? '');

        // Gestion de la catégorie
        if (!empty($data['categorie'])) {
            $categorie = $categorieRepo->find($data['categorie']);
            if ($categorie) {
                $boutique->setCategorieId($categorie);
            }
        }

        //  Gestion des uploads (Logo et Couverture)
        $files = $request->files->all('boutique_form');

        if (!empty($files['photo'])) {
            $photoFile = $files['photo'];
            $newFilename = uniqid().'.'.$photoFile->guessExtension();
            $photoFile->move($this->getParameter('kernel.project_dir').'/public/uploads/boutiques', $newFilename);
            $boutique->setPhoto('uploads/boutiques/'.$newFilename);
        }

        if (!empty($files['photo_couverture'])) {
            $couvertureFile = $files['photo_couverture'];
            $newFilename = uniqid().'.'.$couvertureFile->guessExtension();
            $couvertureFile->move($this->getParameter('kernel.project_dir').'/public/uploads/boutiques', $newFilename);
            $boutique->setPhotoCouverture('uploads/boutiques/'.$newFilename);
        }

        //  Sauvegarder
        if ($isNew) {
            $em->persist($boutique);
        }
        $em->flush();



        return $this->redirectToRoute('seller_dashboard');
    }
    //la fonction qui va gerer les profiles
    #[Route('/boutique/{id}', name: 'app_boutique_profil', requirements: ['id' => '\d+'])]
    public function profil(
        int $id,
        BoutiqueRepository $boutiqueRepo,
        ProduitRepository $produitRepo,
        ProduitCouleurRepository $produitCouleurRepo,
        EntityManagerInterface $em
    ): Response {
        $boutique = $boutiqueRepo->find($id);
       // cas l'id invalide wl boutique mech actif
        if (!$boutique || $boutique->getStatut() !== 'actif') {
            return $this->redirectToRoute('app_boutique');
        }
        //recuperer les produits
        $produits = $produitRepo->findBy(['boutique' => $boutique]);

        // Pour chaque produit récupérer ses couleurs
        $couleursProduits = [];
        foreach ($produits as $produit) {
            $couleursProduits[$produit->getId()] = $produitCouleurRepo->findByProduit($produit);
        }





        return $this->render('boutique/profileBoutique.html.twig', [
            'boutique'         => $boutique,
            'produits'         => $produits,
            'couleursProduits' => $couleursProduits,

        ]);
    }

}

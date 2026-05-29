<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitFormType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProduitController extends AbstractController
{
    // ── 1. Afficher tous les produits (page Explorer) ──
    #[Route('/produits', name: 'produit_index')]
    public function index(Request $request, ProduitRepository $repo): Response
    {
        $nom       = $request->query->get('nom', '');
        $categorie = $request->query->get('categorie', '');
        $boutique  = $request->query->get('boutique', '');

        $produits = $repo->searchAll($nom, $categorie, $boutique);

        return $this->render('produit/TousProduits.html.twig', [
            'produits'  => $produits,
            'nom'       => $nom,
            'categorie' => $categorie,
            'boutique'  => $boutique,
        ]);
    }

    // ── 2. Ajouter un produit ──
    #[Route('/seller/produit/ajouter', name: 'produit_ajouter')]
    public function ajouter(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        // Vérification : seul un vendeur connecté peut accéder
        if (!$this->getUser() || !in_array('ROLE_VENDEUR', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $produit = new Produit();
        $form = $this->createForm(ProduitFormType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ── Gestion de l'image ──
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );

                $produit->setImage($newFilename);
            }

            $produit->setCreatedAt(new \DateTime());

            // Lier le produit à la boutique du vendeur connecté
            $produit->setBoutique($this->getUser()->getBoutique());

            $em->persist($produit);
            $em->flush();

            return $this->redirectToRoute('seller_dashboard');
        }

        return $this->render('produit/ajouter.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ── 3. Modifier un produit ──
    #[Route('/seller/produit/modifier/{id}', name: 'produit_modifier')]
    public function modifier(
        Produit $produit,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        if (!$this->getUser() || !in_array('ROLE_VENDEUR', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProduitFormType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );

                $produit->setImage($newFilename);
            }

            $produit->setUpdatedAt(new \DateTime());
            $em->flush();

            return $this->redirectToRoute('seller_dashboard');
        }

        return $this->render('produit/modifier.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
        ]);
    }

    // ── 4. Supprimer un produit ──
    #[Route('/seller/produit/supprimer/{id}', name: 'produit_supprimer', methods: ['POST'])]
    public function supprimer(
        Produit $produit,
        EntityManagerInterface $em
    ): Response {
        if (!$this->getUser() || !in_array('ROLE_VENDEUR', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $em->remove($produit);
        $em->flush();

        return $this->redirectToRoute('seller_dashboard');
    }

    /**
     * Single product detail page.
     * Mirrors pages/buyer/Produit.php + produits::findById() + produit_couleur query.
     */
    #[Route('/produit/{id}', name: 'produit_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int                    $id,
        ProduitRepository      $produitRepo,
        ProduitCouleurRepository $couleurRepo,
        CartService            $cartService,
    ): Response {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $produit = $produitRepo->findById($id);

        if ($produit === null) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $couleurs  = $couleurRepo->findBy(['produit' => $produit]);
        $cartCount = $cartService->countCartItems($user);

        return $this->render('produit/show.html.twig', [
            'produit'   => $produit,
            'couleurs'  => $couleurs,
            'cartCount' => $cartCount,
        ]);
    }
}
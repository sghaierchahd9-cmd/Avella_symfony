<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitFormType;
use App\Repository\BoutiqueRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class SellerController extends AbstractController
{
    #[Route('/seller/dashboard', name: 'seller_dashboard')]
    public function dashboard(
        BoutiqueRepository $boutiqueRepo,
        ProduitRepository $produitRepo,
        CategorieRepository $categorieRepo,
    ): Response {
        if (!$this->getUser() || !in_array('ROLE_VENDEUR', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $boutique = $boutiqueRepo->findOneBy(['user' => $this->getUser()]);
        $produits = $boutique ? $produitRepo->findBy(['boutique' => $boutique]) : [];

        return $this->render('seller/dashboard.html.twig', [
            'boutique' => $boutique,
            'produits' => $produits,
            'categories'=> $categorieRepo->findAll()
        ]);
    }

    #[Route('/seller/produit/ajouter', name: 'seller_produit_ajouter', methods: ['POST'])]
    public function ajouterProduit(
        Request $request,
        EntityManagerInterface $em,
        BoutiqueRepository $boutiqueRepo,
        SluggerInterface $slugger
    ): Response {
        if (!$this->getUser() || !in_array('ROLE_VENDEUR', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $boutique = $boutiqueRepo->findOneBy(['user' => $this->getUser()]);

        $produit = new Produit();
        $produit->setBoutique($boutique);
        $produit->setCreatedAt(new \DateTime());

        $form = $this->createForm(ProduitFormType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $produit->setImage($newFilename);
            }

            $em->persist($produit);
            $em->flush();
        }

        return $this->redirectToRoute('seller_dashboard');
    }

    #[Route('/seller/produit/modifier/{id}', name: 'seller_produit_modifier', methods: ['POST'])]
    public function modifierProduit(
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
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $produit->setImage($newFilename);
            }

            $produit->setUpdatedAt(new \DateTime());
            $em->flush();
        }

        return $this->redirectToRoute('seller_dashboard');
    }

    #[Route('/seller/produit/supprimer/{id}', name: 'seller_produit_supprimer', methods: ['POST'])]
    public function supprimerProduit(
        Produit $produit,
        EntityManagerInterface $em
    ): Response {
        if (!$this->getUser() || !in_array('ROLE_VENDEUR', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $em->remove($produit);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
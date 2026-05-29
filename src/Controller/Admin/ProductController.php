<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Repository\BoutiqueRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products', name: 'admin_products_')]
#[IsGranted('ROLE_ADMIN')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ProduitRepository $produitRepository,
        BoutiqueRepository $boutiqueRepository,
        CategorieRepository $categorieRepository,
    ): Response {
        $editProduct = null;
        $editId = $request->query->getInt('edit');

        if ($editId > 0) {
            $editProduct = $produitRepository->find($editId);
        }

        return $this->render('admin/products/index.html.twig', [
            'products' => $produitRepository->findBy([], ['createdAt' => 'DESC']),
            'boutiques' => $boutiqueRepository->findBy([], ['nom' => 'ASC']),
            'categories' => $categorieRepository->findBy([], ['nom' => 'ASC']),
            'editProduct' => $editProduct,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        BoutiqueRepository $boutiqueRepository,
        CategorieRepository $categorieRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_product_create', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $product = new Produit();
        $this->fillProduct($product, $request, $boutiqueRepository, $categorieRepository);
        $product->setCreatedAt(new \DateTime());

        $entityManager->persist($product);
        $entityManager->flush();

        $this->addFlash('success', 'Produit ajoute.');

        return $this->redirectToRoute('admin_products_index');
    }

    #[Route('/{id}/update', name: 'update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(
        Produit $product,
        Request $request,
        EntityManagerInterface $entityManager,
        BoutiqueRepository $boutiqueRepository,
        CategorieRepository $categorieRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_product_update_' . $product->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->fillProduct($product, $request, $boutiqueRepository, $categorieRepository);
        $product->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Produit modifie.');

        return $this->redirectToRoute('admin_products_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Produit $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_product_delete_' . $product->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprime.');
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de supprimer ce produit car il est lie a des donnees.');
        }

        return $this->redirectToRoute('admin_products_index');
    }

    private function fillProduct(
        Produit $product,
        Request $request,
        BoutiqueRepository $boutiqueRepository,
        CategorieRepository $categorieRepository,
    ): void {
        $boutique = $boutiqueRepository->find($request->request->getInt('boutique_id'));
        $categorie = $categorieRepository->find($request->request->getInt('categorie_id'));

        if (!$boutique) {
            throw $this->createNotFoundException('Boutique introuvable.');
        }

        $product
            ->setNom(trim((string) $request->request->get('nom')))
            ->setPrix((string) $request->request->get('prix', '0'))
            ->setImage($request->request->get('image') ?: null)
            ->setDescription($request->request->get('description') ?: null)
            ->setBoutique($boutique)
            ->setCategorie($categorie);
    }
}

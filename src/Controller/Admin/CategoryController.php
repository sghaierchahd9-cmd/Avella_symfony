<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories', name: 'admin_categories_')]
#[IsGranted('ROLE_ADMIN')]
final class CategoryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CategorieRepository $categorieRepository): Response
    {
        $editCategory = null;
        $editId = $request->query->getInt('edit');

        if ($editId > 0) {
            $editCategory = $categorieRepository->find($editId);
        }

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categorieRepository->findBy([], ['nom' => 'ASC']),
            'editCategory' => $editCategory,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_category_create', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $category = (new Categorie())
            ->setNom(trim((string) $request->request->get('nom')))
            ->setPhoto($request->request->get('photo') ?: null)
            ->setCreatedAt(new \DateTime());

        $entityManager->persist($category);
        $entityManager->flush();

        $this->addFlash('success', 'Categorie ajoutee.');

        return $this->redirectToRoute('admin_categories_index');
    }

    #[Route('/{id}/update', name: 'update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(Categorie $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_category_update_' . $category->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $category
            ->setNom(trim((string) $request->request->get('nom')))
            ->setPhoto($request->request->get('photo') ?: null);

        $entityManager->flush();

        $this->addFlash('success', 'Categorie modifiee.');

        return $this->redirectToRoute('admin_categories_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Categorie $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_category_delete_' . $category->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Categorie supprimee.');
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de supprimer cette categorie car elle est liee a des donnees.');
        }

        return $this->redirectToRoute('admin_categories_index');
    }
}

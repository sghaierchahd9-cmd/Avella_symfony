<?php

namespace App\Controller\Admin;

use App\Entity\Boutique;
use App\Repository\BoutiqueRepository;
use App\Repository\CategorieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/boutiques', name: 'admin_boutiques_')]
#[IsGranted('ROLE_ADMIN')]
final class BoutiqueController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        BoutiqueRepository $boutiqueRepository,
        CategorieRepository $categorieRepository,
        UserRepository $userRepository,
    ): Response {
        $editBoutique = null;
        $editId = $request->query->getInt('edit');

        if ($editId > 0) {
            $editBoutique = $boutiqueRepository->find($editId);
        }

        return $this->render('admin/boutiques/index.html.twig', [
            'boutiques' => $boutiqueRepository->findBy([], ['created_at' => 'DESC']),
            'categories' => $categorieRepository->findBy([], ['nom' => 'ASC']),
            'users' => $userRepository->findAll(),
            'editBoutique' => $editBoutique,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieRepository $categorieRepository,
        UserRepository $userRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_boutique_create', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $boutique = new Boutique();
        $this->fillBoutique($boutique, $request, $categorieRepository, $userRepository);
        $boutique->setCreatedAt(new \DateTime());

        $entityManager->persist($boutique);
        $entityManager->flush();

        $this->addFlash('success', 'Boutique ajoutee.');

        return $this->redirectToRoute('admin_boutiques_index');
    }

    #[Route('/{id}/update', name: 'update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(
        Boutique $boutique,
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieRepository $categorieRepository,
        UserRepository $userRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_boutique_update_' . $boutique->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->fillBoutique($boutique, $request, $categorieRepository, $userRepository);
        $boutique->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Boutique modifiee.');

        return $this->redirectToRoute('admin_boutiques_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Boutique $boutique, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_boutique_delete_' . $boutique->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $entityManager->remove($boutique);
            $entityManager->flush();
            $this->addFlash('success', 'Boutique supprimee.');
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de supprimer cette boutique car elle est liee a des donnees.');
        }

        return $this->redirectToRoute('admin_boutiques_index');
    }

    private function fillBoutique(
        Boutique $boutique,
        Request $request,
        CategorieRepository $categorieRepository,
        UserRepository $userRepository,
    ): void {
        $categorie = $categorieRepository->find($request->request->getInt('categorie_id'));
        $userId = $request->request->getInt('user_id');
        $user = $userId > 0 ? $userRepository->find($userId) : null;

        if (!$categorie) {
            throw $this->createNotFoundException('Categorie introuvable.');
        }

        $boutique
            ->setNom(trim((string) $request->request->get('nom')))
            ->setDescription($request->request->get('description') ?: null)
            ->setStatut((string) $request->request->get('statut', 'actif'))
            ->setPhoto($request->request->get('photo') ?: null)
            ->setPhotoCouverture($request->request->get('photo_couverture') ?: null)
            ->setCategorieId($categorie)
            ->setUserId($user);
    }
}

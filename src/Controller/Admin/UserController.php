<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $editUser = null;
        $editId = $request->query->getInt('edit');

        if ($editId > 0) {
            $editUser = $userRepository->find($editId);
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $userRepository->findAll(),
            'editUser' => $editUser,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_user_create', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $email = trim((string) $request->request->get('email'));
        $password = (string) $request->request->get('password');

        if ($email === '' || $password === '') {
            $this->addFlash('error', 'Email et mot de passe sont obligatoires.');

            return $this->redirectToRoute('admin_users_index');
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            $this->addFlash('error', 'Un utilisateur avec cet email existe deja.');

            return $this->redirectToRoute('admin_users_index');
        }

        $user = new User();
        $user
            ->setNom(trim((string) $request->request->get('nom')))
            ->setPrenom(trim((string) $request->request->get('prenom')))
            ->setTelephone(trim((string) $request->request->get('telephone')))
            ->setEmail($email)
            ->setRoles([$this->getRoleFromRequest($request)])
            ->setIsVerified(true);

        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur ajoute.');

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/update', name: 'update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(
        User $user,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_user_update_' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $email = trim((string) $request->request->get('email'));

        if ($email === '') {
            $this->addFlash('error', 'Email obligatoire.');

            return $this->redirectToRoute('admin_users_index', ['edit' => $user->getId()]);
        }

        $existingUser = $userRepository->findOneBy(['email' => $email]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $this->addFlash('error', 'Un autre utilisateur utilise deja cet email.');

            return $this->redirectToRoute('admin_users_index', ['edit' => $user->getId()]);
        }

        $user
            ->setNom(trim((string) $request->request->get('nom')))
            ->setPrenom(trim((string) $request->request->get('prenom')))
            ->setTelephone(trim((string) $request->request->get('telephone')))
            ->setEmail($email)
            ->setRoles([$this->getRoleFromRequest($request)]);

        $password = (string) $request->request->get('password');
        if ($password !== '') {
            $user->setPassword($passwordHasher->hashPassword($user, $password));
        }

        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur modifie.');

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_user_delete_' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($this->getUser() instanceof User && $this->getUser()->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('admin_users_index');
        }

        try {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprime.');
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de supprimer cet utilisateur car il est lie a des donnees.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    private function getRoleFromRequest(Request $request): string
    {
        $role = (string) $request->request->get('role', 'ROLE_CLIENT');
        $allowedRoles = ['ROLE_CLIENT', 'ROLE_VENDEUR', 'ROLE_ADMIN'];

        if (!in_array($role, $allowedRoles, true)) {
            return 'ROLE_CLIENT';
        }

        return $role;
    }
}

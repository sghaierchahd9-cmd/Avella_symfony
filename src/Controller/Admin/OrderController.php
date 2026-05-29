<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Entity\CommandeProduits;
use App\Repository\CommandeProduitsRepository;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders', name: 'admin_orders_')]
#[IsGranted('ROLE_ADMIN')]
final class OrderController extends AbstractController
{
    private const STATUSES = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        CommandeRepository $commandeRepository,
        UserRepository $userRepository,
        ProduitRepository $produitRepository,
    ): Response {
        $editOrder = null;
        $editId = $request->query->getInt('edit');

        if ($editId > 0) {
            $editOrder = $commandeRepository->find($editId);
        }

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $commandeRepository->findBy([], ['created_at' => 'DESC']),
            'users' => $userRepository->findAll(),
            'products' => $produitRepository->findBy([], ['nom' => 'ASC']),
            'statuses' => self::STATUSES,
            'editOrder' => $editOrder,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ProduitRepository $produitRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_order_create', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $userRepository->find($request->request->getInt('user_id'));
        $product = $produitRepository->find($request->request->getInt('produit_id'));
        $quantity = max(1, $request->request->getInt('quantite', 1));

        if (!$user || !$product) {
            $this->addFlash('error', 'Client ou produit introuvable.');

            return $this->redirectToRoute('admin_orders_index');
        }

        $total = (float) $product->getPrix() * $quantity;
        $now = new \DateTimeImmutable();

        $order = (new Commande())
            ->setUser($user)
            ->setStatut($this->getStatusFromRequest($request))
            ->setTotal(number_format($total, 3, '.', ''))
            ->setCreatedAt($now)
            ->setUpdatedAt(\DateTime::createFromImmutable($now));

        $item = (new CommandeProduits())
            ->setCommande($order)
            ->setProduit($product)
            ->setQuantite($quantity);

        $entityManager->persist($order);
        $entityManager->persist($item);
        $entityManager->flush();

        $this->addFlash('success', 'Commande ajoutee.');

        return $this->redirectToRoute('admin_orders_index');
    }

    #[Route('/{id}/update', name: 'update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(Commande $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_order_update_' . $order->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $order
            ->setStatut($this->getStatusFromRequest($request))
            ->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Commande modifiee.');

        return $this->redirectToRoute('admin_orders_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Commande $order,
        Request $request,
        EntityManagerInterface $entityManager,
        CommandeProduitsRepository $commandeProduitsRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_order_delete_' . $order->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        foreach ($commandeProduitsRepository->findBy(['commande' => $order]) as $item) {
            $entityManager->remove($item);
        }

        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('success', 'Commande supprimee.');

        return $this->redirectToRoute('admin_orders_index');
    }

    private function getStatusFromRequest(Request $request): string
    {
        $status = (string) $request->request->get('statut', 'en_attente');

        return in_array($status, self::STATUSES, true) ? $status : 'en_attente';
    }
}

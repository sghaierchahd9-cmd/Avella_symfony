<?php

namespace App\DataFixtures;

use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\CommandeProduits;
use App\Entity\Produit;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser('admin@avella.test', 'Admin', 'Avella', '00000000', ['ROLE_ADMIN']);
        $client = $this->createUser('client@avella.test', 'Client', 'Demo', '11111111', ['ROLE_CLIENT']);
        $sellerOne = $this->createUser('seller1@avella.test', 'Nour', 'Atelier', '22222222', ['ROLE_VENDEUR']);
        $sellerTwo = $this->createUser('seller2@avella.test', 'Maya', 'Studio', '33333333', ['ROLE_VENDEUR']);

        foreach ([$admin, $client, $sellerOne, $sellerTwo] as $user) {
            $manager->persist($user);
        }

        $clothes = $this->createCategory('Vetements', 'Vetements.png');
        $accessories = $this->createCategory('Accessoires', 'accessoires.png');
        $home = $this->createCategory('Home Decor', 'HomeDecor.png');

        foreach ([$clothes, $accessories, $home] as $category) {
            $manager->persist($category);
        }

        $atelierNour = $this->createBoutique(
            'Atelier Nour',
            'Pieces artisanales et vetements faits main.',
            $sellerOne,
            $clothes,
            'actif',
            'uploads/boutiques/6a186cb6662e8.jpg',
            'uploads/boutiques/6a186d62a3c6e.jpg'
        );
        $mayaStudio = $this->createBoutique(
            'Maya Studio',
            'Accessoires elegants pour le quotidien.',
            $sellerTwo,
            $accessories,
            'actif',
            'uploads/boutiques/6a18b95252830.png',
            'uploads/boutiques/6a18b95254d21.png'
        );

        foreach ([$atelierNour, $mayaStudio] as $boutique) {
            $manager->persist($boutique);
        }

        $products = [
            $this->createProduct('Robe fluide', 'Robe legere couleur sable.', '89.900', 'prodvend2.png', $atelierNour, $clothes),
            $this->createProduct('Chemise brodee', 'Chemise artisanale brodee a la main.', '64.500', 'prodvend3.png', $atelierNour, $clothes),
            $this->createProduct('Sac tresse', 'Sac pratique avec finition tresse.', '52.000', 'prodvend4.png', $mayaStudio, $accessories),
            $this->createProduct('Bracelet dore', 'Bracelet minimaliste ajuste.', '29.900', 'prodvend5.png', $mayaStudio, $accessories),
        ];

        foreach ($products as $product) {
            $manager->persist($product);
        }

        $confirmedOrder = $this->createOrder($client, 'confirmee');
        $confirmedItem = $this->createOrderItem($confirmedOrder, $products[0], 2);
        $confirmedOrder->setTotal($this->calculateTotal([$confirmedItem]));

        $pendingOrder = $this->createOrder($client, 'en_attente');
        $pendingItemOne = $this->createOrderItem($pendingOrder, $products[2], 1);
        $pendingItemTwo = $this->createOrderItem($pendingOrder, $products[3], 3);
        $pendingOrder->setTotal($this->calculateTotal([$pendingItemOne, $pendingItemTwo]));

        foreach ([$confirmedOrder, $pendingOrder, $confirmedItem, $pendingItemOne, $pendingItemTwo] as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }

    private function createUser(string $email, string $nom, string $prenom, string $telephone, array $roles): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setTelephone($telephone)
            ->setRoles($roles)
            ->setIsVerified(true);

        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        return $user;
    }

    private function createCategory(string $nom, string $photo): Categorie
    {
        return (new Categorie())
            ->setNom($nom)
            ->setPhoto($photo)
            ->setCreatedAt(new \DateTime());
    }

    private function createBoutique(
        string $nom,
        string $description,
        User $user,
        Categorie $categorie,
        string $statut,
        string $photo,
        string $photoCouverture,
    ): Boutique {
        return (new Boutique())
            ->setNom($nom)
            ->setDescription($description)
            ->setUserId($user)
            ->setCategorieId($categorie)
            ->setStatut($statut)
            ->setPhoto($photo)
            ->setPhotoCouverture($photoCouverture)
            ->setCreatedAt(new \DateTime());
    }

    private function createProduct(
        string $nom,
        string $description,
        string $prix,
        string $image,
        Boutique $boutique,
        Categorie $categorie,
    ): Produit {
        return (new Produit())
            ->setNom($nom)
            ->setDescription($description)
            ->setPrix($prix)
            ->setImage($image)
            ->setBoutique($boutique)
            ->setCategorie($categorie)
            ->setCreatedAt(new \DateTime());
    }

    private function createOrder(User $user, string $statut): Commande
    {
        $now = new \DateTimeImmutable();

        return (new Commande())
            ->setUser($user)
            ->setStatut($statut)
            ->setTotal('0.000')
            ->setCreatedAt($now)
            ->setUpdatedAt(\DateTime::createFromImmutable($now));
    }

    private function createOrderItem(Commande $commande, Produit $produit, int $quantite): CommandeProduits
    {
        return (new CommandeProduits())
            ->setCommande($commande)
            ->setProduit($produit)
            ->setQuantite($quantite);
    }

    /**
     * @param CommandeProduits[] $items
     */
    private function calculateTotal(array $items): string
    {
        $total = 0.0;

        foreach ($items as $item) {
            $total += (float) $item->getProduit()->getPrix() * $item->getQuantite();
        }

        return number_format($total, 3, '.', '');
    }
}

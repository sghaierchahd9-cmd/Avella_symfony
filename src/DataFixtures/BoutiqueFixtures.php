<?php
// src/DataFixtures/BoutiqueFixtures.php

namespace App\DataFixtures;

use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BoutiqueFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // ── 1. Catégories ──
        $categoriesData = [
            ['Vêtements',    'vetements.png'],
            ['Accessoires',  'accessoires.png'],
            ['Home Decor',   'homedecor.png'],
            ['Bijoux',       'bijoux.png'],
            ['Cosmétiques',  'cosmetiques.png'],
        ];

        $categories = [];
        foreach ($categoriesData as [$nom, $photo]) {
            $cat = (new Categorie())
                ->setNom($nom)
                ->setPhoto($photo)
                ->setCreatedAt(new \DateTime());
            $manager->persist($cat);
            $categories[] = $cat;
        }

        // ── 2. Vendeurs ──
        $vendeursData = [
            ['nour@test.com',    'Nour',    'Ben Ali'],
            ['maya@test.com',    'Maya',    'Trabelsi'],
            ['sarra@test.com',   'Sarra',   'Hamdi'],
            ['leila@test.com',   'Leila',   'Mansour'],
            ['ines@test.com',    'Ines',    'Chaabane'],
            ['rania@test.com',   'Rania',   'Jouini'],
            ['asma@test.com',    'Asma',    'Belhaj'],
            ['dorra@test.com',   'Dorra',   'Saidi'],
            ['hana@test.com',    'Hana',    'Rezgui'],
            ['amira@test.com',   'Amira',   'Khelil'],
            ['fatma@test.com',   'Fatma',   'Oueslati'],
            ['mariem@test.com',  'Mariem',  'Gharbi'],
            ['olfa@test.com',    'Olfa',    'Ferchichi'],
            ['sirine@test.com',  'Sirine',  'Mrad'],
            ['wafa@test.com',    'Wafa',    'Zouari'],
            ['hajer@test.com',   'Hajer',   'Dridi'],
            ['rim@test.com',     'Rim',     'Slim'],
            ['ahlem@test.com',   'Ahlem',   'Ayari'],
            ['lobna@test.com',   'Lobna',   'Karray'],
            ['sabrine@test.com', 'Sabrine', 'Elloumi'],
        ];

        $vendeurs = [];
        foreach ($vendeursData as [$email, $nom, $prenom]) {
            $user = (new User())
                ->setEmail($email)
                ->setNom($nom)
                ->setPrenom($prenom)
                ->setTelephone('0' . rand(20000000, 99999999))
                ->setRoles(['ROLE_VENDEUR'])
                ->setIsVerified(true);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $vendeurs[] = $user;
        }

        // ── 3. Boutiques ──
        $boutiquesData = [
            ['Atelier Nour',        'Pièces artisanales et vêtements faits main avec amour.',          0],
            ['Maya Studio',         'Accessoires élégants pour sublimer votre quotidien.',             1],
            ['Maison Sarra',        'Décorations uniques pour une maison chaleureuse.',                2],
            ['Bijoux Leila',        'Bijoux fins et raffinés, fabriqués à la main en Tunisie.',        3],
            ['Glow by Ines',        'Cosmétiques naturels et soins de beauté bio.',                    4],
            ['Rania Créations',     'Mode féminine contemporaine inspirée du terroir tunisien.',       0],
            ['L\'Atelier d\'Asma',  'Sacs et maroquinerie artisanale en cuir véritable.',              1],
            ['Dorra Déco',          'Objets déco tendance pour intérieurs modernes.',                  2],
            ['Hana Jewels',         'Colliers, bracelets et bagues en argent et pierres naturelles.',  3],
            ['Amira Beauty',        'Gamme complète de soins pour peau et cheveux.',                   4],
            ['Fatma Couture',       'Robes de mariée et tenues de soirée sur mesure.',                 0],
            ['Mariem Mode',         'Prêt-à-porter féminin tendance à prix accessibles.',              0],
            ['Olfa Home',           'Luminaires, coussins et accessoires pour la maison.',             2],
            ['Sirine Bijoux',       'Créations uniques en or 18 carats et pierres précieuses.',        3],
            ['Wafa Styles',         'Vêtements chics pour femmes actives et modernes.',                0],
            ['Hajer Accessoires',   'Sacs, ceintures et écharpes de qualité premium.',                 1],
            ['Rim Cosmetics',       'Maquillage longue tenue et soins naturels.',                      4],
            ['Ahlem Décorations',   'Artisanat tunisien revisité pour une déco authentique.',          2],
            ['Lobna Collections',   'Accessoires de mode tendance importés et locaux.',                1],
            ['Sabrine Atelier',     'Broderies et textiles traditionnels faits main.',                 0],
        ];

        foreach ($boutiquesData as $i => [$nom, $description, $catIndex]) {
            $boutique = (new Boutique())
                ->setNom($nom)
                ->setDescription($description)
                ->setUserId($vendeurs[$i])
                ->setCategorieId($categories[$catIndex])
                ->setStatut('actif')
                ->setPhoto('')
                ->setPhotoCouverture('')
                ->setCreatedAt(new \DateTime('-' . $i . ' days'));
            $manager->persist($boutique);
        }

        $manager->flush();
    }
}
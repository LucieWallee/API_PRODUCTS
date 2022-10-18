<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Discount;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'une dizaines de produits
        for ($i = 0; $i < 10; $i++) {
            $product = new Product;
            $product->setTitle('Produit ' . $i);
            $product->setDescription('Description du produit ' . $i);
            $product->setPrice(2 + $i);
            $product->setStock(8);
            if($i > 5){
                $product->setEnabled(true);
            } else {
                $product->setEnabled(false);
            }            
            $manager->persist($product);
        }

        // Création des promotions
        $discount1 = new Discount('20% sur la commande', '20TOTAL', 'all', 'percent', 20);
        $manager->persist($discount1);
        $discount2 = new Discount('3ème produit offert', '3EOFFERT', 'quantity', 'free', 0);
        $discount2->setLine(3);
        $manager->persist($discount2);
        // Réfléchir à faire d'une autre façon pour cette remise
        /*$discount3_1 = new Discount('40€ promo 1ère 60%', '40TOTALLIGNE', 'line', 'pourcent', 60);
        $discount3_1->setLine(1);
        $manager->persist($discount3_1);
        $discount3_2 = new Discount('40€ promo 2ème 40% ', '40TOTALLIGNE', 'line', 'pourcent', 40);
        $discount3_2->setLine(2);
        $manager->persist($discount3_2);
        $discount3_3 = new Discount('40€ promo 3ème 20%', '40TOTALLIGNE', 'line', 'pourcent', 20);
        $discount3_3->setLine(3);
        $manager->persist($discount3_3);*/

        // Création d'un utilisateur normal
        $user = new User();
        $user->setEmail("user@api.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
        $manager->persist($user);

        // Création d'un utilisateur administrateur
        $userAdmin = new User();
        $userAdmin->setEmail("admin@api.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password"));
        $manager->persist($userAdmin);

        $manager->flush();
    }
}

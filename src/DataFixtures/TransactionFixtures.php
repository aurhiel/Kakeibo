<?php

namespace App\DataFixtures;

// Entity
use App\Entity\User;
use App\Entity\Category;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// Order / dependencies
use App\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        // Retrieve users list
        $r_users  = $manager->getRepository(User::class);
        $users    = $r_users->findAll();

        dump($users);
        exit;

        // TODO Create users bank account

        // TODO Create random transactions

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            BankBrandFixtures::class,
            CategoryFixtures::class,
            CurrencyFixtures::class,
        ];
    }
}

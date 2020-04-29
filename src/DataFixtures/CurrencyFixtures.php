<?php

namespace App\DataFixtures;

// Entity
use App\Entity\Currency;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;


class CurrencyFixtures extends Fixture implements OrderedFixtureInterface
{

    public function load(ObjectManager $manager)
    {
        $currencies = [
            [ 'name' => "Euro",           'label' => "€", 'slug' => "EUR" ],
            [ 'name' => "Dollar",         'label' => "$", 'slug' => "USD" ],
            [ 'name' => "Livre sterling", 'label' => "£", 'slug' => "GBP" ],
        ];

        foreach ($currencies as $currency)
        {
            // New entity
            $curr = new Currency();

            // Data
            $curr->setName($currency['name']);
            $curr->setLabel($currency['label']);
            $curr->setSlug($currency['slug']);

            // Save
            $manager->persist($curr);
        }

        // Flush
        $manager->flush();
    }

    public function getOrder()
    {
        return 5;
    }
}

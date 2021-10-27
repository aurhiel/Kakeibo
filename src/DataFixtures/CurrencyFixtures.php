<?php

namespace App\DataFixtures;

// Entity
use App\Entity\Currency;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;


class CurrencyFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $currencies = [
            [ 'name' => "Euro",           'label' => "€", 'slug' => "EUR" ],
            [ 'name' => "Dollar",         'label' => "\$US", 'slug' => "USD" ],
            [ 'name' => "Livre sterling", 'label' => "£GB", 'slug' => "GBP" ],
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
}

<?php
namespace App\DataFixtures;

// User entity
use App\Entity\Currency;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;


class CurrencyFixtures extends Fixture implements OrderedFixtureInterface
{

    public function load(ObjectManager $manager)
    {
        $currencies = [
            [
                'name' => "Euro",
                'label' => "€"
            ],
            [
                'name' => "Dollar",
                'label' => "$"
            ],
            [
                'name' => "Livre sterling",
                'label' => "£"
            ],
        ];

        foreach ($currencies as $currency)
        {
            // New entity
            $curr = new Currency();

            $curr->setName($currency['name']);

            $curr->setLabel($currency['label']);

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

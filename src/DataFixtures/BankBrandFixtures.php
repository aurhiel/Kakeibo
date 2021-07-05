<?php

namespace App\DataFixtures;

// Entity
use App\Entity\BankBrand;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;


class BankBrandFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $bank_brands = [
            [ 'label' => "BNP Paribas" ],
            [ 'label' => "Crédit agricole" ],
            [ 'label' => "Caisse d'épargne" ],
            [ 'label' => "Crédit mutuel - CIC" ],
            [ 'label' => "HSBC" ],
            [ 'label' => "Monabanq" ],
            [ 'label' => "Société générale" ],
            [ 'label' => "Autre" ],
        ];

        foreach ($bank_brands as $bank_brand_data)
        {
              // New entity
              $bb = new BankBrand();

              $bb->setLabel($bank_brand_data['label']);

              // Save
              $manager->persist($bb);
        }

        // Flush
        $manager->flush();
    }
}

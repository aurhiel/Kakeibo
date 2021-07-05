<?php

namespace App\DataFixtures;

// Entity
use App\Entity\Category;
use App\Entity\User;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// Order / dependencies
use App\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CategoryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $categories = [
            [ 'label' => 'Divers',          'slug' => 'misc',
              'icon'  => 'credit-card',     'color' => '#a5b1c2' ],

            [ 'label' => 'Banque',          'slug' => 'bank',
              'icon'  => 'bank',            'color' => '#fc5c65',
              'regex' => '(frais retrait)|enjoy|(comm intervention carte)' ],

            [ 'label' => 'Travail',         'slug' => 'work',
              'icon'  => 'cases',           'color' => '#778ca3' ],

            [ 'label' => 'Déplacements',    'slug' => 'travel',
              'icon'  => 'travel-car',      'color' => '#4b6584',
              'regex' => '(total fac)|(rel.elf)|(escota-autoroute)' ],

            [ 'label' => 'Cadeaux',         'slug' => 'gift',
              'icon'  => 'gift',            'color' => '#a55eea' ],

            [ 'label' => 'Divertissement',  'slug' => 'entertainment',
              'icon'  => 'nightlife',       'color' => '#8854d0',
              'regex' => 'cultura|playstation|micromania' ],

            [ 'label' => 'Donations',       'slug' => 'charity',
              'icon'  => 'donation',        'color' => '#4b7bec',
              'regex' => '(medecins du monde)' ],

            [ 'label' => 'Logement',        'slug' => 'housing',
              'icon'  => 'housing',         'color' => '#f7b731',
              'regex' => 'foncia' ],

            [ 'label' => 'Alimentation',    'slug' => 'food',
              'icon'  => 'food',            'color' => '#fa8231',
              'regex' => 'monop|carrefour|deliveroo|ubereats|(Uber([a-z]{2})_EATS)|(columbus cafe)|(bio c bon)' ],

            [ 'label' => 'Vêtements',       'slug' => 'clothes',
              'icon'  => 'clothes',         'color' => '#45aaf2' ],

            [ 'label' => 'Assurances',      'slug' => 'insurance',
              'icon'  => 'security',        'color' => '#2bcbba',
              'regex' => 'allianz|amv' ],

            [ 'label' => 'Santé',           'slug' => 'health',
              'icon'  => 'hospital',        'color' => '#26de81' ],
        ];

        // Retrieve user for icons
        $r_users  = $manager->getRepository(User::class);
        $user     = $r_users->findOneByUsername('Aisekhiel');

        foreach ($categories as $data)
        {
            // New entity
            $category = new Category();

            $category->setLabel($data['label'])
                ->setSlug($data['slug'])
                ->setColor($data['color'])
                ->setIcon($data['icon'])
                ->setIsDefault(true)
                ->setUser($user);

            if (isset($data['regex']))
                $category->setImportRegex($data['regex']);

            // Save
            $manager->persist($category);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
    }
}

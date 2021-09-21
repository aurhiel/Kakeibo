<?php

namespace App\DataFixtures;

// Entity
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Transaction;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// Order / dependencies
use App\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // Retrieve users list
        $r_users  = $manager->getRepository(User::class);
        $users    = $r_users->findAll();

        // Retrieves categories
        $r_categories   = $manager->getRepository(Category::class);
        $categories     = $r_categories->findAllIndexedBy('slug');

        // Define transactions to use later
        $trans_presets_default  = [
            'daily' => [
                [ 'label'     => 'Courses - Monoprix',  'min' => -100,
                  'category'  => 'food',                'max' => -15 ],

                [ 'label'     => 'Columbus Café',       'min' => -15,
                  'category'  => 'food',                'max' => -5 ],

                [ 'label'     => 'Paul - Boulangerie',  'min' => -15,
                  'category'  => 'food',                'max' => -5 ],

                [ 'label'     => 'CB Deliveroo.fr',     'min' => -45,
                  'category'  => 'food',                'max' => -15 ],

                [ 'label'     => 'Retrait DAB',         'min' => -60,
                  'category'  => 'misc',                'max' => -20 ],

                [ 'label'     => 'Cinéma',              'min' => -25,
                  'category'  => 'entertainment',       'max' => -10 ],

                [ 'label'     => 'Essence - Total',     'min' => -20,
                  'category'  => 'travel',              'max' => -50 ],
            ],
            'monthly' => [
                [ 'label'     => 'Virement - Salaire',  'min' => 1600,
                  'category'  => 'work',                'max' => 2600 ],

                [ 'label'     => 'Cotisation compte',   'min' => -10,
                  'category'  => 'bank',                'max' => -8 ],

                [ 'label'     => 'Loyer',               'min' => -800,
                  'category'  => 'housing',             'max' => -600 ],

                [ 'label'     => 'Abo. électricité',    'min' => -60,
                  'category'  => 'housing',             'max' => -40 ],

                [ 'label'     => 'Nintendo Online',     'min' => -3.99,
                  'category'  => 'entertainment',       'max' => -3.99 ],

                [ 'label'     => 'Assurances',          'min' => -80,
                  'category'  => 'insurance',           'max' => -40 ],

                [ 'label'     => 'Médecins du Monde',   'min' => -10,
                  'category'  => 'charity',             'max' => -10 ],
            ]
        ];
        $trans_presets_stark    = [
            'daily' => [
                [ 'label'     => 'Courses - Monoprix',      'min' => -300,
                  'category'  => 'food',                    'max' => -100 ],

                [ 'label'     => 'Starbucks',               'min' => -30,
                  'category'  => 'food',                    'max' => -15 ],

                [ 'label'     => 'La Durée - Boulangerie',  'min' => -70,
                  'category'  => 'food',                    'max' => -25 ],

                [ 'label'     => 'Essence - Total',         'min' => -100,
                  'category'  => 'travel',                  'max' => -250 ],

                [ 'label'     => 'Costumes & chaussures',   'min' => -2600,
                  'category'  => 'travel',                  'max' => -300 ],
            ],
            'monthly' => [
                [ 'label'     => 'Virement - Salaire',  'min' => 128000,
                  'category'  => 'work',                'max' => 128000 ],

                [ 'label'     => 'Cotisation compte',   'min' => -40,
                  'category'  => 'bank',                'max' => -20 ],

                [ 'label'     => 'Loyer',               'min' => -2700,
                  'category'  => 'housing',             'max' => -2700 ],

                [ 'label'     => 'Abo. électricité',    'min' => -249.99,
                  'category'  => 'housing',             'max' => -249.99 ],

                [ 'label'     => 'Assurances',          'min' => -1280,
                  'category'  => 'insurance',           'max' => -840 ],

                [ 'label'     => 'Donations diverses',  'min' => -2400,
                  'category'  => 'charity',             'max' => -1000 ],
            ]
        ];

        // Misc. variables
        $now              = new \DateTime();
        $trans_start_date = new \DateTime('-6 month');

        // Loop on each days from 1 month ago to now
        $old_month = null;
        while($trans_start_date <= $now) {
            $curr_month = (int)$trans_start_date->format('n');
            $trans_date = clone $trans_start_date;

            // Loop on each users in order to add new transactions to their
            //  default bank account
            foreach ($users as $user) {
                $bank_account   = $user->getDefaultBankAccount();
                $trans_presets  = ($user->getUsername() == 'Tony.S') ? $trans_presets_stark : $trans_presets_default;

                // Add monthly transactions
                if ($old_month != $curr_month) {
                    foreach ($trans_presets['monthly'] as $trans_m) {
                        $trans = new Transaction();
                        $amount = ($trans_m['min'] == $trans_m['max']) ? $trans_m['max'] : rand($trans_m['min'], $trans_m['max']);

                        // Set transaction fields
                        $trans->setLabel($trans_m['label'])
                          ->setDate($trans_date)
                          ->setAmount($amount)
                          ->setCategory($categories[$trans_m['category']])
                          ->setBankAccount($bank_account);

                        // Save transaction
                        $manager->persist($trans);
                    }
                }

                // Add daily transactions
                $nb_trans_to_add = rand(2, 4);
                for ($i=0; $i < $nb_trans_to_add; $i++) {
                    // Retrieve a random daily transaction
                    $trans_d = $trans_presets['daily'][rand(0, count($trans_presets['daily']) - 1)];

                    // Create new transaction
                    $trans  = new Transaction();
                    $amount = ($trans_d['min'] == $trans_d['max']) ? $trans_d['max'] : rand($trans_d['min'], $trans_d['max']);

                    // Set transaction fields
                    $trans->setLabel($trans_d['label'])
                      ->setDate($trans_date)
                      ->setAmount($amount)
                      ->setCategory($categories[$trans_d['category']])
                      ->setBankAccount($bank_account);

                    // Save transaction
                    $manager->persist($trans);
                }
            }

            // Increment start date by +1 day & update old month
            $trans_start_date->add(new \DateInterval('P1D'));
            $old_month = $curr_month;

            // dump($trans_start_date);
        }

        // dump($categories);
        // exit;

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

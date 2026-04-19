<?php

declare(strict_types=1);

namespace App\DataFixtures;

// Entity
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Transaction;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// Order / dependencies
use App\DataFixtures\UserFixtures;
use App\Entity\BankAccount;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $manager,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->userRepository->findAll();
        $categories = $this->categoryRepository->findAllIndexedBy('slug');

        // Define transactions to use later
        $trans_presets_default = [
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
        $trans_presets_stark = [
            'daily' => [
                [ 'label'     => 'Courses - Monoprix',      'min' => -300,
                  'category'  => 'food',                    'max' => -100 ],

                [ 'label'     => 'Starbucks',               'min' => -30,
                  'category'  => 'food',                    'max' => -15 ],

                [ 'label'     => 'La Durée - Boulangerie',  'min' => -70,
                  'category'  => 'food',                    'max' => -25 ],

                [ 'label'     => 'Essence - Total',         'min' => -250,
                  'category'  => 'travel',                  'max' => -100 ],

                [ 'label'     => 'Costumes & chaussures',   'min' => -2600,
                  'category'  => 'clothes',                 'max' => -300 ],
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
        $now = new \DateTime();
        $trans_start_date = new \DateTime('-6 month');

        // Loop on each days from 1 month ago to now
        $old_month = null;
        while($trans_start_date <= $now) {
            $curr_month = (int)$trans_start_date->format('n');
            $trans_date = clone $trans_start_date;

            // Loop on each users in order to add new transactions to their default bank account
            foreach ($users as $user) {
                $bank_account = $user->getDefaultBankAccount();
                $trans_presets = ($user->getUsername() == 'Tony.S') ? $trans_presets_stark : $trans_presets_default;

                // Add monthly transactions
                if ($old_month != $curr_month) {
                    foreach ($trans_presets['monthly'] as $trans_m) {
                        $this->createTransaction($bank_account, $categories, $trans_date, $trans_m);
                    }
                }

                // Add daily transactions
                $nb_trans_to_add = random_int(2, 4);
                for ($i=0; $i < $nb_trans_to_add; ++$i) {
                    $this->createTransaction(
                        $bank_account,
                        $categories,
                        $trans_date,
                        // Retrieve a random daily transaction
                        $trans_presets['daily'][random_int(0, count($trans_presets['daily']) - 1)],
                    );
                }
            }

            // Increment start date by +1 day & update old month
            $trans_start_date->add(new \DateInterval('P1D'));
            $old_month = $curr_month;
        }

        $manager->flush();
    }

    private function createTransaction(BankAccount $bank_account, array $categories, \DateTime $trans_date, array $trans_data): void
    {
        $trans  = new Transaction();
        $min = $trans_data['min'];
        $max = $trans_data['max'];

        $amount = match (true) {
            $min === $max => $max,
            $min > $max => random_int($max, $min),
            default => random_int($min, $max),
        };

        $trans->setLabel($trans_data['label'])
            ->setDate($trans_date)
            ->setAmount($amount)
            ->setCategory($categories[$trans_data['category']])
            ->setBankAccount($bank_account)
        ;

        $this->manager->persist($trans);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            BankBrandFixtures::class,
            CategoryFixtures::class,
            CurrencyFixtures::class,
        ];
    }
}

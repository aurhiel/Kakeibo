<?php

namespace App\DataFixtures;

// Entity
use App\Entity\User;
use App\Entity\Currency;
use App\Entity\BankAccount;
use App\Entity\BankBrand;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// Order / dependencies
use App\DataFixtures\BankBrandFixtures;
use App\DataFixtures\CurrencyFixtures;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager)
    {
        // Users
        $users = [
            [
                'username'  => 'Aisekhiel',
                'email'     => 'khielsmail@gmail.com',
                'role'      => 'ROLE_ADMIN'
            ],
            [
                'username'  => 'Aurélien',
                'email'     => 'litti.aurelien@gmail.com',
                'role'      => 'ROLE_ADMIN'
            ],
            [
                'username'  => 'Tony.S',
                'email'     => 'tony.s@stark-industries.com'
            ],
            [
                'username'  => 'Rav3n',
                'email'     => 'raven.roth999@gmail.com'
            ],
            [ 'username'  => 'Mannhart' ],
        ];

        // Retrieve currencies --
        $r_currencies = $manager->getRepository(Currency::class);
        $curr_eur     = $r_currencies->findOneBySlug('EUR');
        $curr_dol     = $r_currencies->findOneBySlug('USD');
        //  -- & bank brands
        $r_bank_brands  = $manager->getRepository(BankBrand::class);
        $b_brand_hsbc   = $r_bank_brands->findOneByLabel("HSBC");
        $b_brand_ce     = $r_bank_brands->findOneByLabel("Caisse d'épargne");

        // Create users
        foreach ($users as $user_data)
        {
            if(isset($user_data['username']))
            {
                // New entity
                $user = new User();

                $user->setUsername($user_data['username']);

                if(isset($user_data['email'])) {
                  $user->setEmail($user_data['email']);
                } else {
                  // Default email
                  $user->setEmail('hello@'.strtolower($user_data['username']).'.fr');
                }

                $user->setPassword($this->hasher->hashPassword($user, 'pass'));

                if(isset($user_data['role']) && !empty($user_data['role'])) {
                    $user->setRole($user_data['role']);
                } else {
                    $user->setRole('ROLE_USER');
                }

                // Save user
                $manager->persist($user);


                // Create user's default bank account
                $bank_account = new BankAccount($user);

                // 'murican ?
                if ($user_data['username'] == 'Tony.S') {
                    $currency = $curr_dol;
                    $bank_brand = $b_brand_hsbc;
                } else {
                    $currency = $curr_eur;
                    $bank_brand = $b_brand_ce;
                }

                // Set bank account fields
                $bank_account->setLabel('Compte courant')
                  ->setCurrency($currency)
                  ->setBankBrand($bank_brand)
                  ->setIsDefault(true);

                // Save user's bank account
                $manager->persist($bank_account);
            }
        }

        // Flush
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CurrencyFixtures::class,
        ];
    }
}

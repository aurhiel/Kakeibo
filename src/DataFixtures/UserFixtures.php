<?php

namespace App\DataFixtures;

// Entity
use App\Entity\User;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
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

                // Save
                $manager->persist($user);
            }
        }

        // Flush
        $manager->flush();
    }
}

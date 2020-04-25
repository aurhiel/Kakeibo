<?php

namespace App\DataFixtures;

// Entity
use App\Entity\User;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture implements OrderedFixtureInterface
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
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
            [ 'username'  => 'Raven' ],
            [ 'username'  => 'Mannhart' ],
            [ 'username'  => 'Patreus' ],
            [ 'username'  => 'Dragonneau' ],
            [ 'username'  => 'Diggory' ],
            [ 'username'  => 'Pichu' ],
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

                $user->setPassword($this->encoder->encodePassword($user, 'pass'));

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

    public function getOrder()
    {
        return 1;
    }
}

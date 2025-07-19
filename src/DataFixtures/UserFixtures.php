<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $adminUser = new User();
        $adminUser->setUsername('Administrator');
        $adminUser->setEmail('admin@example.com');
        $adminUser->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $adminUser,
            'admin123' // You should change this to a more secure password in production
        );
        $adminUser->setPassword($hashedPassword);

        $manager->persist($adminUser);

        // Create standard user
        $standardUser = new User();
        $standardUser->setUsername('Standard User');
        $standardUser->setEmail('user@example.com');
        $standardUser->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $standardUser,
            'user123' // You should change this to a more secure password in production
        );
        $standardUser->setPassword($hashedPassword);

        $manager->persist($standardUser);

        $manager->flush();
    }
}

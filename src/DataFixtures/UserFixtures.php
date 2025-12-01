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
        // Kullanıcılar zaten var mı kontrol et
        $userRepository = $manager->getRepository(User::class);

        // Normal user
        $user = $userRepository->findOneBy(['email' => 'user@example.com']);
        if (!$user) {
            $user = new User();
            $user->setEmail('user@example.com');
            $user->setFullName('Test Kullanıcı');
            $user->setRoles(['ROLE_USER']);
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
            $user->setPassword($hashedPassword);
            $manager->persist($user);
        }

        // Admin user
        $admin = $userRepository->findOneBy(['email' => 'admin@example.com']);
        if (!$admin) {
            $admin = new User();
            $admin->setEmail('admin@example.com');
            $admin->setFullName('Admin Kullanıcı');
            $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
            $admin->setPassword($hashedPassword);
            $manager->persist($admin);
        }

        $manager->flush();
    }
}

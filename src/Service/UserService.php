<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em
    ) {}

    public function updateUser(User $user, string $fullName, string $email, string $newPassword = ''): bool
    {
        // Basit doğrulamalar
        if (trim($fullName) === '' || trim($email) === '') {
            return false;
        }

        // Email benzersizliği kontrolü
        $existing = $this->userRepository->findOneBy(['email' => $email]);
        if ($existing && $existing->getId() !== $user->getId()) {
            return false;
        }

        $user->setFullName($fullName);
        $user->setEmail($email);

        if ($newPassword !== '') {
            $hashed = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashed);
        }

        $this->em->persist($user);
        $this->em->flush();

        return true;
    }
}

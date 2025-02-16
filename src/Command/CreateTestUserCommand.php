<?php

namespace App\Command;

use App\Entity\OnepayUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Creates a test user'
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new OnepayUser();
        $user->setEmail('test@example.com');
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password123')
        );
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPhoneNumber('+1234567890');
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Test user created successfully!');
        $output->writeln('Email: test@example.com');
        $output->writeln('Password: password123');

        return Command::SUCCESS;
    }
}

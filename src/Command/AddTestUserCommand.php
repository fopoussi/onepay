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
    name: 'app:add-test-user',
    description: 'Adds a test user'
)]
class AddTestUserCommand extends Command
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
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPhoneNumber('+1234567890');
        $user->setIsVerified(true);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdateAt(new \DateTime());

        $plaintextPassword = 'test123';
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plaintextPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Test user created:');
        $output->writeln('Email: test@example.com');
        $output->writeln('Password: test123');

        return Command::SUCCESS;
    }
}

<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user')]
class CreateUserCommand extends Command
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        // EMAIL
        $question = new Question('Email: ');
        $email = $helper->ask($input, $output, $question);

        // PASSWORD
        $question = new Question('Password: ');
        $question->setHidden(true);
        $password = $helper->ask($input, $output, $question);

        // ROLE
        $question = new Question('Role (student / teacher / admin): ');
        $roleInput = $helper->ask($input, $output, $question);

        $role = match ($roleInput) {
            'teacher' => 'ROLE_TEACHER',
            'admin' => 'ROLE_ADMIN',
            default => 'ROLE_STUDENT'
        };

        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>User created successfully!</info>');

        return Command::SUCCESS;
    }
}

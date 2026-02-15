<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-mail',
    description: 'Send a test email to verify Gmail configuration',
)]
class TestMailCommand extends Command
{
    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Recipient email address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $recipientEmail = $input->getArgument('email');

        try {
            $email = (new Email())
                ->from('yassine.kaabi@esprit.tn')
                ->to($recipientEmail)
                ->subject('Test Email - EduSmart Password Reset')
                ->text('This is a test email to verify your Gmail SMTP configuration is working correctly.');

            $this->mailer->send($email);

            $output->writeln('<info>✓ Test email sent successfully to ' . $recipientEmail . '</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>✗ Error sending email: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}

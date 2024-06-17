<?php

namespace App\Command;


use App\Helper\G4NTrait;
use App\Service\G4NSendMailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:alertEmails',
    description: 'Monitor all alerts and send alert emails',
)]
class AlertMailCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly G4NSendMailService $g4NSendMailService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Monitor all alerts and send alert emails')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);



       $this->g4NSendMailService->resendAlertMessage();


        $io->success('All alerts have been processed.');

        return Command::SUCCESS;
    }

}

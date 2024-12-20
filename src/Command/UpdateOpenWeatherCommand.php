<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\OpenWeatherService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:updateOpenWeather',
    description: '',
)]
class UpdateOpenWeatherCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly OpenWeatherService $openWeatherService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);

        $io->comment('Lade Open Weather: Alle Anlagen');
        $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No']); // , 'anlView' => 'Yes']);
        $io->progressStart(count($anlagen));
        foreach ($anlagen as $anlage) {
            $ergebniss .= $this->openWeatherService->loadOpenWeather($anlage);
            $io->progressAdvance();
        }
        $io->progressFinish();
        $io->success('Laden Open Weather abgeschlossen!');

        return Command::SUCCESS;
    }
}

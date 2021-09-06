<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\OpenWeatherService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateOpenWeatherCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:updateOpenWeather';

    private AnlagenRepository $anlagenRepository;
    private OpenWeatherService $openWeatherService;

    public function __construct(AnlagenRepository $anlagenRepository, OpenWeatherService $openWeatherService)
    {
        parent::__construct();
        $this->anlagenRepository = $anlagenRepository;
        $this->openWeatherService = $openWeatherService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Laden der Open Weather Daten für die aktuelle Uhrzeit, für alle Anlagen.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);

        $io->comment("Lade Open Weather: Alle Anlagen");
        $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No']); //, 'anlView' => 'Yes']);
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

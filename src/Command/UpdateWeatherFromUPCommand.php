<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\WeatherStationRepository;
use App\Service\DummySollService;
use App\Service\WeatherServiceNew;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:UpdateWeatherUP',
    description: '',
)]
class UpdateWeatherFromUPCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly WeatherStationRepository $weatherStationRepo,
        private readonly WeatherServiceNew $weatherService,
        private readonly DummySollService $dummySollService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('station', 'a', InputOption::VALUE_REQUIRED, 'Wetter Station (ident) für die, Daten geladen werden sollen werden soll')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Datum ab dem die Daten geleaden werden soll')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Datum bis zu dem die Daten geleaden werden soll')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $successMessage = '';
        $io = new SymfonyStyle($input, $output);
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');
        $weatherStationIdent = $input->getOption('station');

        if ($optionFrom) {
            $from = $optionFrom.' 00:00:00';
        } else {
            $from = date('Y-m-d 00:00:00', time());
        }
        if ($optionTo) {
            $to = $optionTo.' 23:59:00';
        } else {
            $to = date('Y-m-d 23:59:00', time());
        }

        $io->success($ergebniss);
        if ($weatherStationIdent) {
            $io->comment("Lade WetterDaten von UP: $from - Anlage ID: $weatherStationIdent");
            $weatherStations = $this->weatherStationRepo->findBy(['databaseIdent' => $weatherStationIdent]);
        } else {
            $io->comment("Lade WetterDaten von UP: $from - Alle UP Wetterstationen");
            $weatherStations = $this->weatherStationRepo->findAllUp();
        }
        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        $counter = 0;
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
            ++$counter;
        }
        $io->progressStart(count($weatherStations));
        foreach ($weatherStations as $weatherStation) {
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
                if (str_starts_with($weatherStation->getType(), 'UP')) {
                    $ergebniss .= $this->weatherService->loadWeatherDataUP($weatherStation, $stamp);
                    $io->progressAdvance();
                }
            }
            $io->comment($weatherStation->getLocation());
        }
        if (!$weatherStationIdent && !$optionFrom && !$optionTo) {
            $successMessage = ' (inkl. Dummydata)';
            $ergebniss .= $this->dummySollService->createDummySoll();
        }
        $io->progressFinish();
        $io->success('Laden der Wetterdaten'.$successMessage.' von UP ist abgeschlossen!');

        return Command::SUCCESS;
    }
}

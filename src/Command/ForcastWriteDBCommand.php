<?php


namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service;
use App\Service\Forecast;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class ForcastWriteDBCommand extends Command {
    use G4NTrait;
    protected static $defaultName = 'pvp:forcastwritedb';

    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private KernelInterface $kernel,
        private ExpectedService $expectedService
    ) {
        parent::__construct();

    }
    protected function configure() {
        $this
            ->setDescription('Build the forcast DB')
            ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'The plant ID to run the calculation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output ){
        $io = new SymfonyStyle($input, $output);
        $anlageId = $input->getOption('anlage');

        $io->comment("Plant ID: $anlageId");

        $input_gb = "53.9569"; // Geo Breite / Latitute
        $input_gl = "9.1504"; // Geo Länge / Longitude

        $datfile_folder = $this->kernel->getProjectDir()."/public/metodat/"; // Metornorm datfile folder

        $input_mer = "15";    // Bezugsmeridan Mitteleuropa
        $input_mn = "35";     // Modulneigung Grad in radiat deg2rad(45) <----
        $input_ma = "180";    // Modul Azimut Grad Wert wenn Ausrichtung nach Süden: 0° nach Südwest: +45° nach Nord: +/-180° nach Osten: -90°
        $input_ab = "0.15";   // Albedo 0.15 Gras 0.3 Dach
        $yearstart = strtotime("-1 year", time());
        $yearende = strtotime("-21 year", time());

        // Start and end between 20 years
        $startapidate = $yearende."0101";
        $endapidate = $yearstart."1231";
        // Meteonorm Datfile prüfen
        $df_gb = str_replace('.', '', $input_gb);
        $df_gl = str_replace('.', '', $input_gl);
        // Meteonorm Datfile name aus Lat und Long ohne punkt
        // function zur umkeissuche anhand lat log fehlt noch
        $datfile_name = "$df_gb-$df_gl.dat";
        $datfile = "$datfile_folder$datfile_name";

        $datfiledata = new TicketsGeneration\Forecast\DatFileReaderService($datfile);


        if ($anlageId) {
            $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlageId]);

        if (count($datfiledata->current()) > 1) {
           # $reg_data = new Service\Forecast\APINasaGovService($input_gl, $input_gb, $startapidate, $endapidate);
            $dec_data = new TicketsGeneration\Forecast\ForcastDEKService($input_gl, $input_gb, $input_mer, $input_mn, $input_ma, $input_ab, $datfiledata);
           # print_R($reg_data->make_sortable_data('faktor'));
            $decarray = $dec_data->get_DEK_Data();

            print_R($this->expectedService->calcExpectedforForecast($anlage, $decarray));

        }

       // $io->progressStart($endapidate);
       // sleep(1);
       // $io->progressAdvance();
       // $io->progressFinish();

        $io->success("Forecast DB completed ".$anlage->getAnlName()." !");


        } else {

        $io->error('No AnlageID ! or Datfile no found !');

        }

        return Command::SUCCESS;
    }

}

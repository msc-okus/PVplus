<?php
namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service;
use App\Service\PdoService;
use App\Service\Forecast;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Service\Forecast\DayAheadForecastDEKService;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'pvp:dayaheadwritedb',
    description: 'Write the day-ahead-forcast data in DB',
)]
class DayAheadWriteDBCommand extends Command {
    use G4NTrait;

    public function __construct(
        private PdoService $pdoService,
        private EntityManagerInterface $entityManager,
        private readonly AnlagenRepository $anlagenRepository,
        private readonly KernelInterface $kernel,
        private readonly Service\ExpectedService $expectedService,
        private readonly DayAheadForecastDEKService $dayAheadForecastDEKService,
        private readonly Forecast\DayAheadForecastMALService $aheadForecastMALService
    ) {
        $this->dayaheadforecastdekservice = $dayAheadForecastDEKService;
        $this->aheadforecastmalservice = $aheadForecastMALService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'the plant ID must set to run the calculation')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output): int{

        $io = new SymfonyStyle($input, $output);
        $anlageId = $input->getOption('anlage');
        $conn = $this->pdoService->getPdoPlant();
        $io->info("Start with plant id: $anlageId");
        $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlageId]);

        if ($anlageId and $anlage) {
        // Inputs die aus der Anlagen DB gelesen werden!
        $usedayforecast = (float)$anlage->getUseDayForecast();  // Yes / No
        $input_gb = (float)$anlage->getAnlGeoLat();       // Geo Breite / Latitute
        $input_gl = (float)$anlage->getAnlGeoLon();       // Geo Länge / Longitude
        $input_mer = (integer)$anlage->getBezMeridan();   // Bezugsmeridan Mitteleuropa
        $input_mn = (integer)$anlage->getModNeigung();    // Modulneigung Grad in radiat deg2rad(45) <----
        $input_ma = (integer)$anlage->getModAzimut();     // Modul Azimut Grad Wert wenn Ausrichtung nach Süden: 0° nach Südwest: +45° nach Nord: +/-180° nach Osten: -90°
        $input_ab = (float)$anlage->getAlbeto();          // Albedo 0.15 Gras 0.3 Dac
        $has_suns_model = (float)$anlage->getHasSunshadingModel(); // check if has sunshading Model
        $DbNameForecast = $anlage->getDbNameForecastDayahead(); // The Name of the Database

        if ($usedayforecast == 1) {
            $io->info("Setting status ! OK");
        } else {
            $io->error("Abort : enable this features in the settings");
            exit();
        }

            $meteo_data = new Service\Forecast\APIOpenMeteoService($input_gl,$input_gb);
            $meteo_array = $meteo_data->make_sortable_data();

            // Wenn meteo data vorhanden
        if ((is_countable($meteo_array) ? count($meteo_array) : 0) > 1) {
            $io->info("Data read ! please wait");
            $decarray = $this->dayaheadforecastdekservice->get_DEK_Data( $input_gl,$input_mer,$input_gb, $input_mn, $input_ma, $input_ab, $meteo_array, $has_suns_model,$anlageId,'all');
            $forcarstarray = $this->aheadForecastMALService->calcforecastout($anlageId,$decarray);
            //  $forcarstarray = $this->expectedService->calcExpectedforDayAheadForecast($anlage, $decarray); //

// only for debuging //
#$h = fopen('decarray.txt', 'w');
#fwrite($h, var_export($decarray, true));
#print_R( $forcarstarray);

            $endprz = 0;
            foreach ($forcarstarray as $interarray) {
                foreach ($interarray as $valueinner) {
                    foreach ($valueinner as $x) {
                        $endprz++;
                    }
                }
            }

            $io->progressStart($endprz);

            if ($endprz > 0) {

                usleep(100000);

                foreach ($forcarstarray as $interarray) {
                   foreach ($interarray as $valueinner) {
                      foreach ($valueinner as $value) {
                          if (array_key_exists('ts', $value)) {
                              $ts = $value['ts'];
                          }
                          if (array_key_exists('ex', $value)) {
                              $ex = $value['ex'];
                          }
                          if (array_key_exists('irr', $value)) {
                              $irr = $value['irr'];
                          }
                          if (array_key_exists('tmp', $value)) {
                              $tmp = $value['tmp'];
                          }
                          if (array_key_exists('gdir', $value)) {
                              $gdir = $value['gdir'];
                          }

                          $updated = date('Y-m-d H:i:s', time());

                          if (array_key_exists('ts', $value)) {

                          $sql_insert = 'INSERT INTO ' . $DbNameForecast . " 
                                SET stamp = '$ts', fc_pac = '$ex', temp = '$tmp', irr = '$irr', gdir = '$gdir', updated_at = '$updated'
                                ON DUPLICATE KEY UPDATE  
                                fc_pac = '$ex', temp = '$tmp', irr = '$irr', gdir = '$gdir', updated_at = '$updated'";

                              $conn->exec($sql_insert);
                              usleep(10000);
                              $io->progressAdvance();
                          }

                      }

                  }

               }

            }
            // Successfull return
            $io->progressFinish();
            $io->success("Day-ahead-forecast DB completed ".$anlage->getAnlName()." !");
            $outs = ["Status" => 'good'];
            echo json_encode($outs);
            return command::SUCCESS;

          } else {
            // Unsuccessfull return
            $io->error('No anlage ID ! or api fail !');
            $outs = ["Status" => 'fail'];
            echo json_encode($outs);
            return command::FAILURE;
         }

        } else {
            // unsuccessfull return
            $io->error('No anlage ID ! or API fail !');
            $outs = ["Status" => 'fail'];
            echo json_encode($outs);
            return command::FAILURE;
        }
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

}
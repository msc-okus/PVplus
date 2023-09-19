<<<<<<< HEAD
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
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(name: 'pvp:forcastwritedb')]
/**
 * @return bool
 */
class ForcastWriteDBCommand extends Command {
    use G4NTrait;
    protected static $defaultName = 'pvp:forcastwritedb';
    protected static $defaultDescription = 'write the forcast DB';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AnlagenRepository $anlagenRepository,
        private KernelInterface $kernel,
        private Service\ExpectedService $expectedService,
        private Forecast\DatFileReaderService $datFileReaderService
    ) {
        parent::__construct();
    }

    protected function configure() {
        $this
            ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'the plant ID must set to run the calculation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int{

        $em = $this->entityManager;
        $io = new SymfonyStyle($input, $output);
        $anlageId = $input->getOption('anlage');

        $io->info("start with plant id: $anlageId");
        $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlageId]);

        if ($anlageId and $anlage) {
        // Inputs die aus der DB werden gelesen
        $input_gb = (float)$anlage->getAnlGeoLat();       // Geo Breite / Latitute
        $input_gl = (float)$anlage->getAnlGeoLon();       // Geo Länge / Longitude
        $input_mer = (integer)$anlage->getBezMeridan();   // Bezugsmeridan Mitteleuropa
        $input_mn = (integer)$anlage->getModNeigung();    // Modulneigung Grad in radiat deg2rad(45) <----
        $input_ma = (integer)$anlage->getModAzimut();     // Modul Azimut Grad Wert wenn Ausrichtung nach Süden: 0° nach Südwest: +45° nach Nord: +/-180° nach Osten: -90°
        $input_ab = (float)$anlage->getAlbeto();          // Albedo 0.15 Gras 0.3 Dac
        // Start und End Datum für die API Abfrage Zeitraum  20 Jahre
        $startapidate = date("Y",strtotime("-21 year", time())).'1231';
        $endapidate = date("Y",strtotime("-1 year", time())).'0101';
        // Function zur Umkreissuche anhand der Lat & Log fehlt noch
        // hole den *.dat File Name aus der Datenbank
        $datfile_name = $anlage->getDatFilename();
        if ($datfile_name) {
            $datfile = "$datfile_name";
            $this->datFileReaderService->read($datfile);
          } else {
            $io->error("abort : load the metodat file first");
            exit();
        }

        // Wenn datfile current
        if (count($this->datFileReaderService->current()) > 1) {
            $io->info("data read ! please wait");
            $reg_data = new Service\Forecast\APINasaGovService($input_gl, $input_gb, $startapidate, $endapidate);
            $dec_data = new Service\Forecast\ForcastDEKService($input_gl, $input_gb, $input_mer, $input_mn, $input_ma, $input_ab, $this->datFileReaderService->current());
            $decarray = $dec_data->get_DEK_Data();
            $reg_array = $reg_data->make_sortable_data('faktor');
            $dec_array = $this->expectedService->calcExpectedforForecast($anlage, $decarray);

// only for debug
#print_R($decarray); // IR Values
#print_R($dec_array);
#print_R($reg_array);
#exit;
            $forcarstarray = $this->array_merge_recursive_distinct($dec_array,$reg_array);
            $endprz = count($forcarstarray) -1;
            $io->progressStart($endprz);
            $query_del = "DELETE FROM `anlage_forcast_day` WHERE `anlage_id` = $anlageId";
            $query = "
            INSERT INTO `anlage_forcast_day`
            SET
                `anlage_id` = :anlageid,
                `day` = :doy,
                `expected_day` = :expectedday,
                `factor_day` = :factorday,
                `factor_min` = :factormin,
                `factor_max` = :factormax,
                `pr_day` = :prday,
                `pr_kumuliert` = :prkumuliert, 	
                `pr_day_ft` = :prdayft,	
                `pr_kumuliert_ft` = :prkumuliertft,
                `irrday` = :irrday, 	
                `updated_at` = :updatedat
            ON DUPLICATE KEY UPDATE 
                `anlage_id` = :anlageid,
                `day` = :doy,
                `expected_day` = :expectedday,
                `factor_day` = :factorday,
                `factor_min` = :factormin,
                `factor_max` = :factormax,
                `pr_day` = :prday,
                `pr_kumuliert` = :prkumuliert, 	
                `pr_day_ft` = :prdayft,	
                `irrday` = :irrday, 
                `updated_at` = :updatedat
            ;
            ";

            if ($endprz > 1) {
                $em->getConnection()->executeQuery($query_del);
                usleep(100000);

                foreach ($forcarstarray as $key => $value) {

                    $expEvuSumYear = $forcarstarray['yearsum']['exp_evu_year'];
                    $expTheoSumYear = $forcarstarray['yearsum']['exp_theo_year'];
                    $cgp = $forcarstarray['yearsum']['CGP'];

                    if (array_key_exists('doy', $value)) {
                        $doy = $value['doy'];
                    }
                    if (array_key_exists('exp_evu_day', $value)) {
                        $expEvuSumDay = $value['exp_evu_day'];
                    }
                    if (array_key_exists('faktor_min', $value)) {
                        $faktormin = $value['faktor_min'];
                    }
                    if (array_key_exists('faktor_max', $value)) {
                        $faktormax = $value['faktor_max'];
                    }
                    if (array_key_exists('pr_theo_skal', $value)) {
                        $prday = $value['pr_theo_skal'];
                    }
                    if (array_key_exists('pr_theo_komu', $value)) {
                        $prkumuliert = $value['pr_theo_komu'];
                    }
                    if (array_key_exists('pr_theo_ft_skal', $value)) {
                        $prdayft = $value['pr_theo_ft_skal'];
                    }
                    if (array_key_exists('pr_theo_komu', $value)) {
                        $prkumuliertft = $value['pr_theo_ft_komu'];
                    }
                    if (array_key_exists('irrday', $value)) {
                        $irrday = $value['irrday'];
                    }

                    $updated = date ('Y-m-d H:i:s', time());

                    $faktorday = number_format($expEvuSumDay / $expTheoSumYear, 8, ".", "");

                    if (array_key_exists('doy', $value)) {

                        $queryParams = [
                            'anlageid' => $anlageId,
                            'doy' => $doy,
                            'expectedday' => $expEvuSumDay,
                            'factorday' => $faktorday,
                            'factormin' => $faktormin,
                            'factormax' => $faktormax,
                            'prday' => $prday,
                            'prkumuliert' => $prkumuliert,
                            'prdayft' => $prdayft,
                            'prkumuliertft' => $prkumuliertft,
                            'irrday' => $irrday,
                            'updatedat' => $updated,
                        ];

                        $em->getConnection()->executeQuery(
                            $query,
                            $queryParams
                        );

                        usleep(250000);

                        $em->clear();
                        $io->progressAdvance();
                    }
                }
            }
            // successfull return
            $io->progressFinish();
            $io->success("forecast DB completed ".$anlage->getAnlName()." !");
            $outs = array("status" => 'good');
            echo json_encode($outs);
            return command::SUCCESS;

          } else {
            $io->error("the dat file could not be found for anlage ID ! ". $datfile_name);
            // unsuccessfull return
            $io->error('no anlage ID ! or datfile not found !');
            $outs = array("status" => 'fail');
            echo json_encode($outs);
            return command::FAILURE;
         }

        } else {
            // unsuccessfull return
            $io->error('no anlage ID ! or datfile not found !');
            $outs = array("status" => 'fail');
            echo json_encode($outs);
            return command::FAILURE;
        }
        return \Symfony\Component\Console\Command\Command::SUCCESS;

    }
    // Helper for Array Merge
    private function array_merge_recursive_distinct(array $array1, array $array2) {
        $merged = $array1;
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

}
=======
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
>>>>>>> 47126e0af2fa6bf8d3fa797c34e97a1ccfc26bb7

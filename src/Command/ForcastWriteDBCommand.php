<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service;
use App\Service\Forecast;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Service\Forecast\ForcastDEKService;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'pvp:forcastwritedb',
    description: 'Write the forcast DB',
)]
class ForcastWriteDBCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly EntityManagerInterface        $entityManager,
        private readonly AnlagenRepository             $anlagenRepository,
        private readonly KernelInterface               $kernel,
        private readonly Service\ExpectedService       $expectedService,
        private readonly Forecast\DatFileReaderService $datFileReaderService,
        private readonly ForcastDEKService             $forcastDEKService,
    )
    {
        $this->forcastdekservice = $forcastDEKService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'the plant ID must set to run the calculation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;
        $io = new SymfonyStyle($input, $output);
        $anlageId = $input->getOption('anlage');

        $io->info("start with plant id: $anlageId");
        $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlageId]);

        if ($anlageId and $anlage) {
            // Inputs die aus der DB werden gelesen
            $usedayforecast = (float)$anlage->getUseDayForecast();  // Yes / No
            $input_gb = (float)$anlage->getAnlGeoLat();       // Geo Breite / Latitute
            $input_gl = (float)$anlage->getAnlGeoLon();       // Geo Länge / Longitude
            $input_mer = (integer)$anlage->getBezMeridan();   // Bezugsmeridan Mitteleuropa
            $input_mn = (integer)$anlage->getModNeigung();    // Modulneigung Grad in radiat deg2rad(45) <----
            $input_ma = (integer)$anlage->getModAzimut();     // Modul Azimut Grad Wert wenn Ausrichtung nach Süden: 0° nach Südwest: +45° nach Nord: +/-180° nach Osten: -90°
            $input_ab = (float)$anlage->getAlbeto();          // Albedo 0.15 Gras 0.3 Dac
            $has_suns_model = (float)$anlage->getHasSunshadingModel(); // check if has sunshading Model
            // Start und End Datum für die API Abfrage Zeitraum 20 Jahre
            $startapidate = date("Y", strtotime("-21 year", time())) . '1231';
            $endapidate = date("Y", strtotime("-1 year", time())) . '0101';
            // Funktion zur Umkreissuche anhand der Lat & Log fehlt noch
            // hole den *.dat File Name aus der Datenbank
            $datfile_name = $anlage->getDatFilename();

            if ($usedayforecast == 1) {

                if ($datfile_name) {
                    $datfile = "$datfile_name";
                    $this->datFileReaderService->read($datfile);
                } else {
                    $io->error("abort : load the metodat file first");
                    exit();
                }

            } else {
                $io->error("abort : enable this features in the settings");
                exit();
            }

            // Wenn datfile is current
            if ((is_countable($this->datFileReaderService->current()) ? count($this->datFileReaderService->current()) : 0) > 1) {
                $io->info("data read ! please wait");
                $reg_data = new Service\Forecast\APINasaGovService($input_gl, $input_gb, $startapidate, $endapidate);
                $decarray = $this->forcastdekservice->get_DEK_Data($input_gl, $input_mer, $input_gb, $input_mn, $input_ab, $this->datFileReaderService->current(), $has_suns_model, $anlageId, 'all');
                $reg_array = $reg_data->make_sortable_data('faktor');
                $dec_array = $this->expectedService->calcExpectedforForecast($anlage, $decarray);

// only for debuging //
#$h = fopen('decarrayyes.txt', 'w');
#print_R($decarray); // IR Values
#print_R($dec_array);
#print_R($reg_array);
#fwrite($h, var_export($dec_array, true));
exit;
                $forcarstarray = self::array_merge_recursive_distinct($dec_array, $reg_array);
                $endprz = (is_countable($forcarstarray) ? count($forcarstarray) : 0) - 1;
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

                        $updated = date('Y-m-d H:i:s', time());

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
                $io->success("forecast DB completed " . $anlage->getAnlName() . " !");
                $outs = ["status" => 'good'];
                echo json_encode($outs);
                return command::SUCCESS;

            } else {
                $io->error("the dat file could not be found for anlage ID ! " . $datfile_name);
                // unsuccessfull return
                $io->error('no anlage ID ! or datfile not found !');
                $outs = ["status" => 'fail'];
                echo json_encode($outs);
                return command::FAILURE;
            }

        } else {
            // unsuccessfull return
            $io->error('no anlage ID ! or datfile not found !');
            $outs = ["status" => 'fail'];
            echo json_encode($outs);
            return command::FAILURE;
        }

    }

}
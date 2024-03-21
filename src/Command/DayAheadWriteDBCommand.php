<?php
/**
 * MS 12/23
 * Das day-ahed Command zum Erechnen der day-ahed Forecast Daten!
 * Alle Fc day-ahed Daten, werden anhand der vorhanden Anlagen -AC und der -Modultemp, sowie -Einstahlung Daten auf Langzeit
 * gesucht und mithilfe der berechneten Forecast -Strahlung und -Modultemp (nach NREL) die aus einem 10 KM Radius zu abgefragten Standort,
 * mithilfe der Multiple Regression gesucht, verglichen und in eine DB geschreiben werden.
 * Aufruf für alle Anlagen -> pvp:dayaheadwritedb
 * Aufruf für eine einzelne Anlage -> pvp:dayaheadwritedb -a [anlagen id]
 */

namespace App\Command;
use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service;
use App\Service\PdoService;
use App\Service\Forecast;
use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

    protected function configure(): void {
        $this->addOption('anlage', 'a', InputArgument::OPTIONAL, 'The plant ID can be set to run the calculation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $anlageId = $input->getOption('anlage');
        $conn = $this->pdoService->getPdoPlant();

        if ($anlageId) {
            $io->info("Start with plant id: $anlageId");
            $anlagen = $this->anlagenRepository->findIdLike([$anlageId]);
          } else {
            $anlagen = $this->anlagenRepository->findAllIDByUseDayahead();
        }

        if ($anlagen) {

            foreach ($anlagen as $anlage) {
                // Die Inputs aus der Anlagen DB werden gelesen!
                $anlageId = $anlage->getAnlId(); // the Anlagen ID
                $usedayforecast = (float)$anlage->getUseDayaheadForecast();  // Yes / No
                $input_gb = (float)$anlage->getAnlGeoLat();       // Geo Breite / Latitute
                $input_gl = (float)$anlage->getAnlGeoLon();       // Geo Länge / Longitude
                $input_mer = (integer)$anlage->getBezMeridan();   // Bezugsmeridan Mitteleuropa
                $input_mn = (integer)$anlage->getModNeigung();    // Modulneigung Grad in radiat deg2rad(45) <----
                //$input_ma = (integer)$anlage->getModAzimut();     // Modul Azimut Grad Wert wenn Ausrichtung nach Süden: 0° nach Südwest: +45° nach Nord: +/-180° nach Osten: -90°
                $input_ab = (float)$anlage->getAlbeto();          // Albedo 0.15 Gras 0.3 Dac
                $has_suns_model = (float)$anlage->getHasSunshadingModel(); // Check if has sunshading Model
                $DbNameForecast = $anlage->getDbNameForecastDayahead(); // The Name of the Database
                // Ersteinmal ein paar Checks
                if ($usedayforecast == 1) {
                    $io->info("Setting status ! OK");
                  } else {
                    $io->error("Abort : Enable this features in the settings");
                    exit();
                }
                if (is_numeric($input_gl) and is_numeric($input_gb)) {
                    // Hier werden jetzt die Meteo Daten geholt
                    $meteo_data = new Service\Forecast\APIOpenMeteoService($input_gl, $input_gb);
                    $meteo_array = $meteo_data->make_sortable_data();
                  } else {
                    $io->error("Abort : Geo information is incorrect");
                    exit();
                }

                // Wenn Meteo daten vorhanden sind, dann Verarbeite diese.
                if ((is_countable($meteo_array) ? count($meteo_array) : 0) > 1) {
                    $io->info("Data read ! please wait");

                    $decarray = $this->dayaheadforecastdekservice->get_DEK_Data($input_gl, $input_mer, $input_gb, $input_mn, $input_ab, $meteo_array, $has_suns_model, $anlageId, 'all');
                    $forcarstarray = $this->aheadForecastMALService->calcforecastout($anlageId, $decarray);
                    $endprz = 0;

                    foreach ($forcarstarray as $interarray) {
                        foreach ($interarray as $valueinner) {
                            foreach ($valueinner as $x) {
                                $endprz++;
                            }
                        }
                    }

                    $io->progressStart($endprz);
                    $DbNamereal = explode('.', $DbNameForecast);
                    if ($endprz > 0) {
                        // Prüfen ob datenbank existiert;
                        $sql_abf = "select exists (select null from information_schema.tables where table_name='".$DbNamereal[1]."')";
                        $result = $conn->query($sql_abf);
                        $row = $result->fetch(PDO::FETCH_BOTH);
                        if ($row[0] != 1) {
                            $sql_create =  "CREATE TABLE IF NOT EXISTS ".$DbNameForecast." (
                                      `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                                      `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                                      `fc_pac` varchar(20) DEFAULT NULL,
                                      `temp` varchar(20) DEFAULT NULL,
                                      `irr` varchar(20) DEFAULT NULL,
                                      `gdir` varchar(20) DEFAULT NULL,
                                      `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                                      PRIMARY KEY (`db_id`),
                                      UNIQUE KEY `unique_ist_record` (`stamp`) USING BTREE,
                                      KEY `stamp` (`stamp`)
                                    ) ENGINE=InnoDB AUTO_INCREMENT=44161 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
                            $conn->exec($sql_create);
                        }
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
                                        // Schreibt die Daten in die Datenbank
                                        $sql_insert = 'INSERT INTO ' . $DbNameForecast . " 
                                        SET stamp = '$ts', fc_pac = '$ex', temp = '$tmp', irr = '$irr', gdir = '$gdir', updated_at = '$updated'
                                        ON DUPLICATE KEY UPDATE  
                                        fc_pac = '$ex', temp = '$tmp', irr = '$irr', gdir = '$gdir', updated_at = '$updated'";

                                        $conn->exec($sql_insert);
                                        // etwas warten um weiter Daten zu schreiben.
                                        usleep(10000);
                                        $io->progressAdvance();

                                    }

                                }

                            }

                        }

                    }
                    // Successfull return
                    $io->progressFinish();
                    $io->success("Day-ahead-forecast DB completed " . $anlage->getAnlName() . " !");

                } else {
                    // Unsuccessfull return
                    $io->error('No anlage ID ! or api fail !');
                    echo json_encode(http_response_code(400));

                }
            }
        } else {
            // Unsuccessfull return
            $io->error('No anlage ID ! or API fail !');
            echo json_encode(http_response_code(201));
            return command::FAILURE;
        }

        echo json_encode(http_response_code(201));
        return Command::SUCCESS;
    }

}

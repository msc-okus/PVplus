<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AnlagenRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ApiResource(
    shortName: 'anlages',
    operations: [
        new GetCollection(normalizationContext: ['groups' => 'api:read']),
        new Get(normalizationContext: ['groups' => 'api:read'])
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    paginationItemsPerPage: 30,
    security: 'ROLE_ADMIN, ROLE_API_USER',


)]
#[ApiFilter(SearchFilter::class, properties: ['anlName' => 'partial'])]
/**
 * ApiResource(
 *     security="is_granted('ROLE_ADMIN')",
 *     collectionOperations={
 *      "get"={"security"="is_granted('ROLE_API_USER')"},
 *      "post"
 *      },
 *     itemOperations={
 *     "get"={"security"="is_granted('ROLE_API_USER')"},
 *     "put"
 *     },
 *     shortName="anlages",
 *     normalizationContext={"groups"={"api:read"}},
 *     denormalizationContext={"groups"={"api:write"}},
 *     attributes={
 *          "pagination_items_per_page"=30,
 *          "formats"={ "json", "jsonld","html", "csv"={"text/csv"}}
 *     }
 * )
 * ApiFilter(SearchFilter::class, properties={"anlName":"partial"})
 *
 */
#[ORM\Table(name: 'anlage')]
#[ORM\Entity(repositoryClass: \App\Repository\AnlagenRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Anlage implements \Stringable
{
    private string $dbAnlagenData = 'pvp_data';
    private string $dbAnlagenBase = 'pvp_base';

    #[Groups(['main','api:read'])]
    #[SerializedName('id')]
    #[ORM\Column(name: 'id', type: 'bigint', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private string $anlId;  // DBAL return Type of bigint = string

    #[Groups(['main'])]
    #[ORM\Column(name: 'eigner_id', type: 'bigint', nullable: false)]
    private string $eignerId;

    #[Groups(['main'])]
    #[ORM\Column(name: 'anl_type', type: 'string', length: 25, nullable: false)]
    private string $anlType;

    #[Deprecated]
    #[ORM\Column(name: 'anl_dbase', type: 'string', length: 25, nullable: false, options: ['default' => 'web32_db2'])]
    private string $anlDbase = 'web32_db2'; // ready to delete

    #[ORM\Column(name: 'anl_betrieb', type: 'date', nullable: true)]
    private ?DateTime $anlBetrieb = null;

    #[Groups(['main','api:read'])]
    #[SerializedName('plant_name')]
    #[ORM\Column(name: 'anl_name', type: 'string', length: 50, nullable: false)]
    private string $anlName;

    #[ORM\Column(name: 'anl_strasse', type: 'string', length: 100, nullable: false)]
    private string $anlStrasse;

    #[Groups(['main'])]
    #[ORM\Column(name: 'anl_plz', type: 'string', length: 10, nullable: false)]
    private string $anlPlz;

    #[Groups(['main'])]
    #[ORM\Column(name: 'anl_ort', type: 'string', length: 100, nullable: false)]
    private string $anlOrt;

    #[Groups(['main'])]
    #[ORM\Column(name: 'anl_intnr', type: 'string', length: 50, nullable: true)]
    private ?string $anlIntnr = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $customPlantId = '';

    #[Groups(['main','api:read'])]
    #[SerializedName('p_nom')]
    #[ORM\Column(type: 'string', length: 20)]
    private string $power = '0';

    #[Groups(['api:read'])]
    #[SerializedName('p_nom_east')]
    #[ORM\Column(type: 'string', length: 20)]
    private string $powerEast = '0';

    #[Groups(['api:read'])]
    #[SerializedName('p_nom_west')]
    #[ORM\Column(type: 'string', length: 20)]
    private string $powerWest = '0';

    #[Deprecated]
    #[ORM\Column(name: 'anl_data_go_ws', type: 'string', length: 10, nullable: false, options: ['default' => 'No'])]
    private string $anlDataGoWs = 'No'; // ready to delete

    #[Deprecated]
    #[ORM\Column(name: 'anl_modul_anz', type: 'string', length: 50, nullable: false)]
    private string $anlModulAnz = ''; // ready to delete

    #[Deprecated]
    #[ORM\Column(name: 'anl_modul_name', type: 'string', length: 100, nullable: false)]
    private string $anlModulName = ''; // ready to delete

    #[Deprecated]
    #[ORM\Column(name: 'anl_modul_leistung', type: 'string', length: 50, nullable: false)]
    private string $anlModulLeistung = ''; // ready to delete

    #[Deprecated]
    #[ORM\Column(name: 'anl_db_ist', type: 'string', length: 50, nullable: false)]
    private string $anlDbIst = '';

    #[Deprecated]
    #[ORM\Column(name: 'anl_db_ws', type: 'string', length: 50, nullable: false)]
    private string $anlDbWs = '';

    #[Deprecated]
    #[ORM\Column(name: 'anl_same_ws', type: 'string', length: 10, nullable: false, options: ['default' => 'No'])]
    private string $anlSameWs = 'No';

    #[ORM\Column(name: 'send_warn_mail', type: 'boolean')]
    private bool $sendWarnMail = false;

    #[ORM\Column(name: 'anl_input_daily', type: 'string', length: 10, nullable: false, options: ['default' => 'No'])]
    private string $anlInputDaily = 'No';

    #[Deprecated]
    #[ORM\Column(name: 'anl_grupe', type: 'string', length: 10, nullable: false, options: ['default' => 'No'])]
    private string $anlGruppe = 'No'; // ready to delete

    #[Deprecated]
    #[ORM\Column(name: 'anl_grupe_dc', type: 'string', length: 10, nullable: false, options: ['default' => 'No'])]
    private string $anlGruppeDc = 'No'; // ready to delete

    #[ORM\Column(name: 'anl_zeitzone', type: 'string', length: 50, nullable: false)]
    private string $anlZeitzone = '0';

    #[ORM\Column(name: 'anl_db_unit', type: 'string', length: 10, nullable: true, options: ['default' => 'kwh'])]
    private ?string $anlDbUnit = 'kwh';

    #[Deprecated]
    #[ORM\Column(name: 'anl_wind_unit', type: 'string', length: 10, nullable: false, options: ['default' => 'km/h'])]
    private string $anlWindUnit = 'km/h'; // ready to delete

    #[ORM\Column(name: 'anl_view', type: 'string', length: 10, nullable: false, options: ['default' => 'No'])]
    private string $anlView = 'No';

    #[ORM\Column(name: 'anl_hide_plant', type: 'string', length: 10, nullable: false)]
    private string $anlHidePlant = 'No';

    #[Groups(['api:read'])]
    #[SerializedName('plant_location_lat')]
    #[ORM\Column(name: 'anl_geo_lat', type: 'string', length: 30, nullable: false)]
    private string $anlGeoLat = '';

    #[Groups(['api:read'])]
    #[SerializedName('plant_location_lon')]
    #[ORM\Column(name: 'anl_geo_lon', type: 'string', length: 30, nullable: false)]
    private string $anlGeoLon = '';

    #[ORM\Column(name: 'anl_mute', type: 'string', length: 10, nullable: false)]
    private string $anlMute = 'No';

    #[ORM\Column(name: 'anl_mute_until', type: 'datetime', nullable: true)]
    private ?DateTime $anlMuteUntil = null;

    #[ORM\ManyToOne(targetEntity: Eigner::class, inversedBy: 'anlage')]
    private ?Eigner $eigner = null;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageAcGroups::class, cascade: ['persist', 'remove'])]
    private Collection $acGroups;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageEventMail::class, cascade: ['persist', 'remove'])]
    private Collection $eventMails;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlagenReports::class, cascade: ['remove'])]
    private Collection $anlagenReports;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageAvailability::class, cascade: ['remove'])]
    #[ORM\OrderBy(['inverter' => 'ASC'])]
    private Collection $availability;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlagenStatus::class, cascade: ['remove'])]
    private Collection $status;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlagenPR::class, cascade: ['remove'])]
    private Collection $pr;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $useNewDcSchema = true;

    #[ORM\Column(type: 'boolean')]
    private bool $useCosPhi = false;

    #[Deprecated]
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $useCustPRAlgorithm = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $pldAlgorithm = 'Lelystad';

    #[ORM\Column(type: 'boolean')]
    private bool $showOnlyUpperIrr = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showStringCharts = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showAvailability = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showAvailabilitySecond = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showInverterPerformance = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showMenuReporting = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showMenuDownload = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showEvuDiag = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showInverterOutDiag = true;

    #[ORM\Column(type: 'boolean')]
    private bool $showCosPhiDiag = false;

    #[ORM\Column(type: 'boolean')]
    private bool $showCosPhiPowerDiag = false;

    #[ORM\Column(type: 'boolean')]
    #[Deprecated]
    private bool $showGraphDcCurrInv = false;

    #[ORM\Column(type: 'boolean')]
    #[Deprecated]
    private bool $showGraphDcCurrGrp = false;

    #[ORM\Column(type: 'boolean')]
    #[Deprecated]
    private bool $showGraphVoltGrp = false; // ready to delete

    #[ORM\Column(type: 'boolean')]
    #[Deprecated]
    private bool $showGraphDcInverter = false; // ready to delete

    #[ORM\Column(type: 'boolean')]
    #[Deprecated]
    private bool $showGraphIrrPlant = false; // ready to delete

    #[ORM\Column(type: 'boolean')]
    private bool $showPR = false;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrLimitAvailability = '0';

    #[ORM\Column(type: 'string', length: 20)]
    private string $contractualAvailability = '100';

    #[ORM\Column(type: 'string', length: 20)]
    private string $contractualPR = '100';

    #[ORM\Column(type: 'string', length: 20)]
    private string $contractualPower = '0';

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageCase5::class, cascade: ['persist', 'remove'])]
    private Collection $anlageCase5s;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageCase6::class, cascade: ['persist', 'remove'])]
    private Collection $anlageCase6s;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlagePVSystDaten::class, cascade: ['persist', 'remove'])]
    private Collection $anlagePVSystDatens;

    #[ORM\Column(type: 'boolean')]
    private bool $showPvSyst = false;

    #[ORM\ManyToOne(targetEntity: WeatherStation::class, cascade: ['persist'], inversedBy: 'anlagen')]
    private ?weatherStation $weatherStation = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $pacDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $facDate = null;

    #[ORM\Column(type: 'boolean')]
    private bool $usePac = false;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageForcast::class, cascade: ['persist', 'remove'])]
    private Collection $anlageForecasts;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageForcastDay::class, cascade: ['persist', 'remove'])]
    private Collection $anlageForecastDays;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageGroups::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['dcGroup' => 'ASC'])]
    private Collection $groups;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageModules::class, cascade: ['persist', 'remove'])]
    private Collection $modules;

    #[ORM\Column(type: 'boolean')]
    private bool $isOstWestAnlage = false;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $threshold1PA0 = '0';
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $threshold1PA1 = '0';
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $threshold1PA2 = '0';
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $threshold1PA3 = '0';

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $threshold2PA0 = '0';
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $threshold2PA1 = '50';
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $threshold2PA2 = '50';
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $threshold2PA3 = '50';

    #[ORM\Column(type: 'boolean')]
    private bool $usePAFlag0 = false;
    #[ORM\Column(type: 'boolean')]
    private bool $usePAFlag1 = false;
    #[ORM\Column(type: 'boolean')]
    private bool $usePAFlag2 = false;
    #[ORM\Column(type: 'boolean')]
    private bool $usePAFlag3 = false;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $paFormular0 = '2'; // 2 = ti / titheo
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $paFormular1 = '1'; // 1 = ti / (titheo - tiFM)
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $paFormular2 = '1'; // 1 = ti / (titheo - tiFM)
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $paFormular3 = '1'; // 1 = ti / (titheo - tiFM)

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $prFormular0 = null;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $prFormular1 = null;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $prFormular2 = null;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $prFormular3 = null;
    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: TimesConfig::class, cascade: ['persist', 'remove'])]
    private Collection $timesConfigs;
    #[ORM\Column(type: 'boolean')]
    private bool $showForecast = false;
    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageGridMeterDay::class)]
    private Collection $anlageGridMeterDays;
    #[ORM\Column(type: 'boolean')]
    private bool $useGridMeterDayData = false;
    #[ORM\Column(type: 'string', length: 20)]
    private string $country = '';
    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: OpenWeather::class, cascade: ['persist', 'remove'] )]
    private Collection $openWeather;
    #[ORM\Column(type: 'boolean')]
    private bool $calcPR = false;
    #[ORM\Column(type: 'string', length: 20)]
    private string $pacDuration = '';
    #[Groups(['api:read'])]
    #[SerializedName('p_nom_simulation')]
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $kwPeakPvSyst = null;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $kwPeakPLDCalculation = null;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $designPR = null;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $facDateStart = null;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $pacDateEnd = null;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private string $lid;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private float|string|null $annualDegradation = null;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $pldPR = null;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $treatingDataGapsAsOutage = true;
    #[ORM\Column(type: 'string', length: 20)]
    private string $epcReportType = '';

    #[Deprecated]
    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlagenPvSystMonth::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['month' => 'ASC'])]
    private Collection $anlagenPvSystMonths;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlagenMonthlyData::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['year' => 'ASC', 'month' => 'ASC'])]
    private Collection $anlagenMonthlyData;

    #[ORM\Column(type: 'string', length: 20)]
    private string $transformerTee = '';

    #[ORM\Column(type: 'string', length: 20)]
    private string $guaranteeTee = '';

    #[ORM\Column(type: 'string', length: 20)]
    private string $pldYield = '';

    #[ORM\Column(type: 'string', length: 30)]
    #[Groups(['api:read'])]
    #[SerializedName('project_number')]
    private string $projektNr = '';

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageLegendReport::class, cascade: ['persist', 'remove'])]
    private Collection $anlageLegendReports;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $Notes = null;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageMonth::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $anlageMonth;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageInverters::class)]
    private Collection $Inverters;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempCorrCellTypeAvg = '25';

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempCorrGamma = '-0.4';

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempCorrA = '-3.56';

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempCorrB = '-0.0750';

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempCorrDeltaTCnd = '3.0';

    #[ORM\Column(type: 'string', length: 20)]
    private string $degradationPR = '0.5';

    #[ORM\Column(type: 'string', length: 20)]
    private string $pldNPValue = '';

    #[ORM\Column(type: 'boolean')]
    private bool $usePnomForPld = false;

    #[ORM\Column(type: 'string', length: 20)]
    private string $pldDivisor = '';

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $epcReportStart = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $epcReportEnd = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $inverterStartVoltage = '540';

    #[ORM\Column(type: 'boolean')]
    private bool $useLowerIrrForExpected = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $epcReportNote = null;

    #[ORM\Column(type: 'integer')]
    private int $configType;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: Log::class)]
    private Collection $logs;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $hasDc = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $hasStrings = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $hasPPC = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $usePPC = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $hasPannelTemp = false;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: Ticket::class, cascade: ['remove'])]
    private Collection $tickets;

    #[ORM\OneToOne(mappedBy: 'anlage', targetEntity: EconomicVarNames::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?EconomicVarNames $economicVarNames = null;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: EconomicVarValues::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Collection $economicVarValues;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $useDayForecast = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $useDayaheadForecast = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $hasSunshadingModel  = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isTrackerEow  = false;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $degradationForecast = '0';

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $lossesForecast = '5';

    #[ORM\OneToMany(mappedBy: 'plant', targetEntity: AnlageFile::class, orphanRemoval: true)]
    private Collection $anlageFiles;

    #[ORM\OneToOne(mappedBy: 'anlage', targetEntity: AnlageSettings::class, cascade: ['persist', 'remove'])]
    private ?AnlageSettings $settings = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $picture = '';

    #[ORM\OneToMany(mappedBy: 'Anlage', targetEntity: Status::class, orphanRemoval: true)]
    private Collection $statuses;

    #[ORM\Column(type: 'boolean')]
    private bool $hasWindSpeed = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $DataSourceAM = null;

    #[ORM\Column(type: 'boolean')]
    private bool $RetrieveAllData = false;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: DayLightData::class, cascade: ['persist', 'remove'])]
    private Collection $dayLightData;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageSunShading::class, cascade: ['persist', 'remove'])]
    private Collection $anlageSunShading;

    #[ORM\Column(type: 'string', length: 20)]
    private string $freqTolerance = '2.0';

    #[ORM\Column(type: 'string', length: 20)]
    private string $freqBase = '50';

    #[ORM\Column(type: 'boolean')]
    private ?bool $hasFrequency = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $excludeFromExpCalc = false;

    #[ORM\Column(nullable: true)]
    private ?bool $useAcGroupsAsSection = null;

    #[ORM\Column]
    private ?bool $ignoreNegativEvu = true;

    #[ORM\Column(nullable: true)]
    private ?bool $expectedTicket = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $percentageDiff = "20";

    #[ORM\Column(nullable: true)]
    private ?bool $weatherTicket = false;

    #[ORM\Column]
    private ?bool $ActivateTicketSystem = false;

    #[ORM\Column(type: 'boolean')]
    private ?bool $internalTicketSystem = false;


    #[ORM\Column]
    private ?bool $kpiTicket = false;

    #[ORM\Column(nullable: true)]
    private ?string $pathToImportScript = null;

    #[ORM\Column(nullable: true)]
    private ?bool $gridTicket = false;

    #[ORM\Column(nullable: true)]
    private ?bool $newAlgorythm = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $DCCableLosses = "0";

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $MissmatchingLosses = "0";

    #[ORM\Column(length: 100,  nullable: true)]
    private ?string $InverterEfficiencyLosses = "0";

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ShadingLosses = "0";

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ACCableLosses = "0";

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $TransformerLosses = "0";

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $transformerLimitation = "0";

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $inverterLimitation = "0";

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dynamicLimitations = "0";

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $PowerThreshold = "0";

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageSensors::class, cascade: ['persist', 'remove'])]
    private Collection $sensors;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlagePpcs::class, cascade: ['persist', 'remove'])]
    private Collection $ppcs;

    #[ORM\Column(name: 'bez_meridan', type: 'string', length: 20, nullable: true)]
    private ?string $bezMeridan = '';


    #[ORM\Column(name: 'mod_neigung', type: 'string', length: 20, nullable: true)]
    private ?string $modNeigung = '';


    #[ORM\Column(name: 'mod_azimut', type: 'string', length: 20, nullable: true)]
    private ?string $modAzimut = '';


    #[ORM\Column(name: 'albeto', type: 'string', length: 20, nullable: true)]
    private ?string $albeto = '';

    #[ORM\Column(name: 'dat_filename', type: 'string', nullable: true)]
    private ?string $datFilename = null;

    #[ORM\Column(nullable: true)]
    private ?bool $ppcBlockTicket = false;

    #[ORM\OneToMany(mappedBy: 'anlage', targetEntity: AnlageStringAssignment::class)]
    private Collection $anlageStringAssignments;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastAnlageStringAssigmentUpload = null;



    /**
     * @return string|null
     */
    public function getPathToImportScript(): ?string
    {
        return $this->pathToImportScript;
    }

    /**
     * @param string|null $pathToImportScript
     */
    public function setPathToImportScript(?string $pathToImportScript): void
    {
        $this->pathToImportScript = $pathToImportScript;
    }

    public function __construct()
    {
        $this->acGroups = new ArrayCollection();
        $this->availability = new ArrayCollection();
        $this->status = new ArrayCollection();
        $this->anlagenReports = new ArrayCollection();
        $this->pr = new ArrayCollection();
        $this->eventMails = new ArrayCollection();
        $this->anlageCase5s = new ArrayCollection();
        $this->anlageCase6s = new ArrayCollection();
        $this->anlagePVSystDatens = new ArrayCollection();
        $this->anlageForecasts = new ArrayCollection();
        $this->anlageForecastDays = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->modules = new ArrayCollection();
        $this->timesConfigs = new ArrayCollection();
        $this->anlageGridMeterDays = new ArrayCollection();
        $this->openWeather = new ArrayCollection();
        $this->anlagenPvSystMonths = new ArrayCollection();
        $this->anlagenMonthlyData = new ArrayCollection();
        $this->anlageLegendReports = new ArrayCollection();
        $this->anlageMonth = new ArrayCollection();
        $this->Inverters = new ArrayCollection();
        $this->logs = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->economicVarValues = new ArrayCollection();
        $this->anlageFiles = new ArrayCollection();
        $this->statuses = new ArrayCollection();
        $this->dayLightData = new ArrayCollection();
        $this->anlageSunShading = new ArrayCollection();
        $this->sensors = new ArrayCollection();
        $this->ppcs = new ArrayCollection();
        $this->anlageStringAssignments = new ArrayCollection();
    }


    public function getDatFilename(): ?string
    {
        return $this->datFilename;
    }


    public function setDatFilename($datFilename): void
    {
        $this->datFilename = $datFilename;
    }



    public function getBezMeridan(): string
    {
        return $this->bezMeridan;
    }


    public function setBezMeridan(string $bezMeridan): void
    {
        $this->bezMeridan = $bezMeridan;
    }


    public function getModNeigung(): string
    {
        return $this->modNeigung;
    }


    public function setModNeigung(string $modNeigung): void
    {
        $this->modNeigung = $modNeigung;
    }


    public function getModAzimut(): string
    {
        return $this->modAzimut;
    }


    public function setModAzimut(string $modAzimut): void
    {
        $this->modAzimut = $modAzimut;
    }


    public function getAlbeto(): string
    {
        return $this->albeto;
    }


    public function setAlbeto(string $albeto): void
    {
        $this->albeto = $albeto;
    }




    public function getAnlId(): string
    {
        return $this->anlId;
    }

    public function setAnlId($anlagenId): self
    {
        $this->anlId = $anlagenId;

        return $this;
    }

    public function getAnlagenId(): ?string
    {
        return $this->anlId;
    }

    public function getEignerId(): ?string
    {
        return $this->eignerId;
    }

    public function setEignerId(string $eignerId): self
    {
        $this->eignerId = $eignerId;

        return $this;
    }

    public function getAnlType(): ?string
    {
        return $this->anlType;
    }

    public function setAnlType(?string $anlType): self
    {
        $this->anlType = $anlType;

        return $this;
    }

    public function getAnlBetrieb(): ?DateTime
    {
        return $this->anlBetrieb;
    }

    public function getBetriebsJahre(): float
    {
        if ($this->getAnlBetrieb()) {
            $interval = $this->getAnlBetrieb()->diff(new DateTime());

            return (int) ($interval->format('%a') / 356) + 1;
        } else {
            return -1;
        }

    }

    public function setAnlBetrieb(?DateTime $anlBetrieb): self
    {
        $this->anlBetrieb = $anlBetrieb;

        return $this;
    }

    public function getAnlName(): ?string
    {
        return $this->anlName;
    }

    public function setAnlName(string $anlName): self
    {
        $this->anlName = $anlName;

        return $this;
    }

    public function getAnlStrasse(): ?string
    {
        return $this->anlStrasse;
    }

    public function setAnlStrasse(string $anlStrasse): self
    {
        $this->anlStrasse = $anlStrasse;

        return $this;
    }

    public function getAnlPlz(): ?string
    {
        return $this->anlPlz;
    }

    public function setAnlPlz(string $anlPlz): self
    {
        $this->anlPlz = $anlPlz;

        return $this;
    }

    public function getAnlOrt(): ?string
    {
        return $this->anlOrt;
    }

    public function setAnlOrt(string $anlOrt): self
    {
        $this->anlOrt = $anlOrt;

        return $this;
    }

    public function getAnlIntnr(): ?string
    {
        return $this->anlIntnr;
    }

    public function setAnlIntnr(?string $anlIntnr): self
    {
        $this->anlIntnr = $anlIntnr;

        return $this;
    }

    public function getCustomPlantId(): ?string
    {
        return $this->customPlantId;
    }

    public function setCustomPlantId(?string $customPlantId): void
    {
        $this->customPlantId = $customPlantId;
    }

    /**
     * Replaced by getPnom
     * @deprecated  */
    public function getPower(): ?string
    {
        return $this->power;
    }

    /**
     * Replaced by setPnom
     * @deprecated
     */
    public function setPower(string $power): self
    {
        $this->power = str_replace(',', '.', $power);

        return $this;
    }


    public function getPnom(): ?float
    {
        return (float) $this->power;
    }

    public function setPnom(string $power): self
    {
        $this->power = str_replace(',', '.', $power);

        return $this;
    }

    /**
     * Replaced by getPnom
     * @deprecated
     */
    public function getKwPeak(): ?float
    {
        return (float) $this->power;
    }

    /**
     * Replaced by setPnom
     * @deprecated  */
    public function setKwPeak(string $power): self
    {
        $this->power = str_replace(',', '.', $power);

        return $this;
    }

    public function getPowerEast(): ?float
    {
        return (float) $this->powerEast;
    }

    public function setPowerEast(string $powerEast): self
    {
        $this->powerEast = str_replace(',', '.', $powerEast);

        return $this;
    }

    public function getPowerWest(): ?float
    {
        return (float) $this->powerWest;
    }

    public function setPowerWest(string $powerWest): self
    {
        $this->powerWest = str_replace(',', '.', $powerWest);

        return $this;
    }
/*
    public function getAnlDbIst(): ?string
    {
        return $this->anlDbIst;
    }

    public function setAnlDbIst(string $anlDbIst): self
    {
        $this->anlDbIst = $anlDbIst;

        return $this;
    }

    public function getAnlDbWs(): ?string
    {
        return $this->anlDbWs;
    }

    public function setAnlDbWs(string $anlDbWs): self
    {
        $this->anlDbWs = $anlDbWs;

        return $this;
    }

    public function getAnlSameWs(): ?string
    {
        return $this->anlSameWs;
    }

    public function setAnlSameWs(string $anlSameWs): self
    {
        $this->anlSameWs = $anlSameWs;

        return $this;
    }
*/
    public function getSendWarnMail(): ?bool
    {
        return $this->sendWarnMail;
    }

    public function setSendWarnMail(string $sendWarnMail): self
    {
        $this->sendWarnMail = $sendWarnMail;

        return $this;
    }

    public function getAnlInputDaily(): ?string
    {
        return $this->anlInputDaily;
    }

    public function setAnlInputDaily(string $anlInputDaily): self
    {
        $this->anlInputDaily = $anlInputDaily;

        return $this;
    }

    public function getAnlZeitzone(): ?float
    {
        return (float) $this->anlZeitzone;
    }

    public function setAnlZeitzone(string $anlZeitzone): self
    {
        $this->anlZeitzone = $anlZeitzone;

        return $this;
    }

    public function getAnlZeitzoneWs(): ?string
    {
        if ($this->getWeatherStation()) {
            return $this->getWeatherStation()->gettimeZoneWeatherStation();
        } else {
            return false;
        }
    }

    public function getAnlZeitzoneIr(): ?string
    {
        return $this->getWeatherStation()->gettimeZoneWeatherStation();
    }

    public function setAnlZeitzoneWs(string $anlZeitzoneWs): self
    {
        $weatherStation = $this->getWeatherStation();
        $weatherStation->settimeZoneWeatherStation($anlZeitzoneWs);

        return $this;
    }

    public function getAnlIrChange(): ?string
    {
        if ($this->getWeatherStation()) {
            return $this->getWeatherStation()->getChangeSensor() ? '1' : '0';
        } else {
            return false;
        }
    }

    public function setAnlIrChange(string $irrChange): self
    {
        $weatherStation = $this->getWeatherStation();
        $weatherStation->setChangeSensor($irrChange);

        return $this;
    }
    /** @deprecated  */
    public function getAnlDbUnit(): ?string
    {
        return $this->anlDbUnit;
    }
    /** @deprecated  */
    public function setAnlDbUnit(?string $anlDbUnit): self
    {
        $this->anlDbUnit = $anlDbUnit;

        return $this;
    }
    /** @deprecated  */
    public function getAnlView(): ?string
    {
        return $this->anlView;
    }
    /** @deprecated  */
    public function setAnlView(string $anlView): self
    {
        $this->anlView = $anlView;

        return $this;
    }
    public function getAnlHidePlant(): ?string
    {
        return $this->anlHidePlant;
    }

    public function setAnlHidePlant(string $anlHidePlant): self
    {
        $this->anlHidePlant = $anlHidePlant;

        return $this;
    }

    public function getAnlGeoLat(): ?float
    {
        return (float)$this->anlGeoLat;
    }

    public function setAnlGeoLat(string $anlGeoLat): self
    {
        $this->anlGeoLat = $anlGeoLat;

        return $this;
    }

    public function getAnlGeoLon(): ?float
    {
        return (float)$this->anlGeoLon;
    }

    public function setAnlGeoLon(string $anlGeoLon): self
    {
        $this->anlGeoLon = $anlGeoLon;

        return $this;
    }

    public function getAnlMute(): ?string
    {
        return $this->anlMute;
    }

    public function setAnlMute(string $anlMute): self
    {
        $this->anlMute = $anlMute;

        return $this;
    }

    public function getAnlMuteUntil(): ?DateTime
    {
        return $this->anlMuteUntil;
    }

    public function setAnlMuteUntil(DateTime $anlMuteUntil): self
    {
        $this->anlMuteUntil = $anlMuteUntil;

        return $this;
    }

    public function getEigner(): ?Eigner
    {
        return $this->eigner;
    }

    public function setEigner(?Eigner $eigner): self
    {
        $this->eigner = $eigner;

        return $this;
    }

    public function getDbNameIst(): string
    {
        return $this->dbAnlagenData.'.db__pv_ist_'.$this->getAnlIntnr();
    }

    public function getDbNameAcIst(): string
    {
        return $this->dbAnlagenData.'.db__pv_ist_'.$this->getAnlIntnr();
    }

    public function getDbNameIstDc(): string
    {
        return $this->dbAnlagenData.'.db__pv_dcist_'.$this->getAnlIntnr();
    }

    public function getDbNameDCIst(): string
    {
        return $this->dbAnlagenData.'.db__pv_dcist_'.$this->getAnlIntnr();
    }

    /** @deprecated use getDBNameSoll() */
    public function getDbNameAcSoll(): string
    {
        return $this->dbAnlagenData.'.db__pv_soll_'.$this->getAnlIntnr();
    }

    public function getDbNameSoll(): string
    {
        return $this->dbAnlagenData.'.db__pv_soll_'.$this->getAnlIntnr();
    }

    public function getDbNameDcSoll(): string
    {
        return $this->dbAnlagenData.'.db__pv_dcsoll_'.$this->getAnlIntnr();
    }

    public function getDbNameForecastDayahead(): string
    {
        return $this->dbAnlagenData.'.db__pv_fc_'.$this->getAnlIntnr();
    }

    public function getDbNameMeters(): string
    {
        return $this->dbAnlagenData.'.db__pv_meters_'.$this->getAnlIntnr();
    }

    public function getDbNamePPC(): string
    {
        return $this->dbAnlagenData.'.db__pv_ppc_'.$this->getAnlIntnr();
    }

    public function getDbNameSensorsData(): string
    {
        return $this->dbAnlagenData.'.db__pv_sensors_data_'.$this->getAnlIntnr();
    }
    public function getDbNameAnalgeSensors(): string
    {
        return $this->dbAnlagenBase.'.anlage_sensors_'.$this->getAnlIntnr();
    }

    public function getDbNameDivisionsStringTable(): string
    {
        return $this->dbAnlagenData.'.db__string_pv_'.$this->getAnlIntnr();
    }
    public function getDbNameSection(): string
    {
        return $this->dbAnlagenData.'.db__pv_section_'.$this->getAnlIntnr();
    }

    // get Weather Database
    public function getNameWeather(): ?string
    {
        return $this->getWeatherStation()->getDatabaseIdent();
    }

    public function getDbNameWeather(): string
    {
        return $this->dbAnlagenData.'.db__pv_ws_'.$this->getNameWeather();
    }

    public function getAcGroups(): Collection
    {
        return $this->acGroups;
    }

    public function addAcGroup(AnlageAcGroups $acGroup): self
    {
        if (!$this->acGroups->contains($acGroup)) {
            $this->acGroups[] = $acGroup;
            $acGroup->setAnlage($this);
        }

        return $this;
    }

    public function removeAcGroup(AnlageAcGroups $acGroup): self
    {
        if ($this->acGroups->contains($acGroup)) {
            $this->acGroups->removeElement($acGroup);
            // set the owning side to null (unless already changed)
            if ($acGroup->getAnlage() === $this) {
                $acGroup->setAnlage(null);
            }
        }

        return $this;
    }

    public function getGroupsAc(): array
    {
        $gruppe = [];

        /** @var AnlageAcGroups $group */
        foreach ($this->getAcGroups() as $group) {
            $gruppe[$group->getAcGroup()] = [
                'GMIN' => $group->getUnitFirst(),
                'GMAX' => $group->getUnitLast(),
                'INVNR' => $group->getAcGroup(),
                'GroupName' => $group->getAcGroupName(),
            ];
        }

        return $gruppe;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getInverterFromAnlage(): array
    {
        $nameArray = [];

        switch ($this->getConfigType()) {
            case 1: // In diesem Fall gibt es keine SCBs; AC Gruppen = Trafo oder ähnliches; DC Gruppen = Inverter
                foreach ($this->getGroups() as $inverter) {
                    $nameArray[$inverter->getDcGroup()] = $inverter->getDcGroupName();
                }
                break;
            case 2: // In diesem Fall gibt es keine SCBs; AC Gruppen = DC Gruppen = Inverter Bsp: Lelystad
            case 3: // AC Gruppen = Inverter; DC Gruppen = SCB Gruppen Bsp: Groningen
            case 4: // AC Gruppen = Inverter; DC Gruppen = SCBs Bsp: Guben
                foreach ( $this->getAcGroups() as $inverter) {
                    $nameArray[$inverter->getAcGroup()] = $inverter->getAcGroupName();
                }
                break;
        }

        return $nameArray;
        /*
        return $this->cache->get('getNameInverterArray_'.md5($this->getAnlId()), function(CacheItemInterface $cacheItem)
        {
            $cacheItem->expiresAfter(120); // Lifetime of cache Item in secunds


            $nameArray = [];

            switch ($this->getConfigType()) {
                case 1: // In diesem Fall gibt es keine SCBs; AC Gruppen = Trafo oder ähnliches; DC Gruppen = Inverter
                    foreach ($this->getGroups() as $inverter) {
                        $nameArray[$inverter->getDcGroup()] = $inverter->getDcGroupName();
                    }
                    break;
                case 2: // In diesem Fall gibt es keine SCBs; AC Gruppen = DC Gruppen = Inverter Bsp: Lelystad
                case 3: // AC Gruppen = Inverter; DC Gruppen = SCB Gruppen Bsp: Groningen
                case 4: // AC Gruppen = Inverter; DC Gruppen = SCBs Bsp: Guben
                    foreach ( $this->getAcGroups() as $inverter) {
                        $nameArray[$inverter->getAcGroup()] = $inverter->getAcGroupName();
                    }
                    break;
            }

            return $nameArray;

        });
*/
    }

    public function getAnzInverter(): int
    {
        $anzInverter = 0;
        if ($this->getConfigType() == '3' | $this->getConfigType() == '4') {
            $anzInverter = $this->getAcGroups()->count();
        } else {
            foreach ($this->getAcGroups() as $group) {
                $anzInverter += $group->getUnitLast() - $group->getUnitFirst() + 1;
            }
        }

        return $anzInverter;
    }

    public function getAnzInverterFromGroupsAC(): int
    {
        return $this->getAnzInverter();
    }

    public function getAvailability(): Collection
    {
        return $this->availability;
    }

    public function addAvailability(AnlageAvailability $availability): self
    {
        if (!$this->availability->contains($availability)) {
            $this->availability[] = $availability;
            $availability->setAnlage($this);
        }

        return $this;
    }

    public function removeAvailability(AnlageAvailability $availability): self
    {
        if ($this->availability->contains($availability)) {
            $this->availability->removeElement($availability);
            // set the owning side to null (unless already changed)
            if ($availability->getAnlage() === $this) {
                $availability->setAnlage(null);
            }
        }

        return $this;
    }

    public function getLastStatus(): Collection
    {
        $criteria = AnlagenRepository::lastAnlagenStatusCriteria();

        return $this->status->matching($criteria);
    }

    public function addStatus(AnlagenStatus $status): self
    {
        if (!$this->status->contains($status)) {
            $this->status[] = $status;
            $status->setAnlage($this);
        }

        return $this;
    }

    public function removeStatus(AnlagenStatus $status): self
    {
        if ($this->status->contains($status)) {
            $this->status->removeElement($status);
            // set the owning side to null (unless already changed)
            if ($status->getAnlage() === $this) {
                $status->setAnlage(null);
            }
        }

        return $this;
    }

    public function getPr(): Collection
    {
        return $this->pr;
    }

    public function getLastPR(): Collection
    {
        $criteria = AnlagenRepository::lastAnlagenPRCriteria();

        return $this->pr->matching($criteria);
    }

    public function getYesterdayPR(): ?Collection
    {
        $criteria = AnlagenRepository::yesterdayAnlagenPRCriteria();

        return $this->pr->matching($criteria);
    }

    public function addPr(AnlagenPR $pr): self
    {
        if (!$this->pr->contains($pr)) {
            $this->pr[] = $pr;
            $pr->setAnlage($this);
        }

        return $this;
    }

    public function removePr(AnlagenPR $pr): self
    {
        if ($this->pr->contains($pr)) {
            $this->pr->removeElement($pr);
            // set the owning side to null (unless already changed)
            if ($pr->getAnlage() === $this) {
                $pr->setAnlage(null);
            }
        }

        return $this;
    }

    public function getShowOnlyUpperIrr(): ?bool
    {
        return $this->showOnlyUpperIrr;
    }

    public function setShowOnlyUpperIrr(bool $showOnlyUpperIrr): self
    {
        $this->showOnlyUpperIrr = $showOnlyUpperIrr;

        return $this;
    }

    public function getShowStringCharts(): ?bool
    {
        return $this->showStringCharts;
    }

    public function setShowStringCharts(bool $showStringCharts): self
    {
        $this->showStringCharts = $showStringCharts;

        return $this;
    }

    public function getShowAvailability(): ?bool
    {
        return $this->showAvailability;
    }

    public function setShowAvailability(bool $showAvailability): self
    {
        $this->showAvailability = $showAvailability;

        return $this;
    }

    public function getShowAvailabilitySecond(): ?bool
    {
        return $this->showAvailabilitySecond;
    }

    public function setShowAvailabilitySecond(bool $showAvailabilitySecond): self
    {
        $this->showAvailabilitySecond = $showAvailabilitySecond;

        return $this;
    }

    public function getShowInverterPerformance(): ?bool
    {
        return $this->showInverterPerformance;
    }

    public function setShowInverterPerformance(bool $showInverterPerformance): self
    {
        $this->showInverterPerformance = $showInverterPerformance;

        return $this;
    }

    public function getShowMenuReporting(): ?bool
    {
        return $this->showMenuReporting;
    }

    public function setShowMenuReporting(bool $showMenuReporting): self
    {
        $this->showMenuReporting = $showMenuReporting;

        return $this;
    }

    public function getShowMenuDownload(): ?bool
    {
        return $this->showMenuDownload;
    }

    public function setShowMenuDownload(bool $showMenuDownload): self
    {
        $this->showMenuDownload = $showMenuDownload;

        return $this;
    }
    /**
     * @deprecated
     * use configType === 3 or 4 instead
     */
    public function getUseNewDcSchema(): ?bool
    {
        if ($this->configType === 1 or $this->configType === 2){
            return false;
        }

        return true;
    }

    /**
     * @deprecated
     */
     public function setUseNewDcSchema(bool $useNewDcSchema): self
    {
        $this->useNewDcSchema = $useNewDcSchema;

        return $this;
    }

    // use this to check if EVU is used
    public function getShowEvuDiag(): ?bool
    {
        return $this->showEvuDiag;
    }

    public function setShowEvuDiag(bool $showEvuDiag): self
    {
        $this->showEvuDiag = $showEvuDiag;

        return $this;
    }

    public function getShowCosPhiDiag(): ?bool
    {
        return $this->showCosPhiDiag;
    }

    public function setShowCosPhiDiag(bool $showCosPhiDiag): self
    {
        $this->showCosPhiDiag = $showCosPhiDiag;

        return $this;
    }

    public function getShowCosPhiPowerDiag(): ?bool
    {
        return $this->showCosPhiPowerDiag;
    }

    public function setShowCosPhiPowerDiag(bool $showCosPhiPowerDiag): self
    {
        $this->showCosPhiPowerDiag = $showCosPhiPowerDiag;


        return $this;
    }

    /**
     * @return array
     */
    public function getGroupsDc()
    {
        $gruppe = [];
        /** @var AnlageGroups $row */
        foreach ($this->getGroups() as $row) {
            $grpnr = $row->getDcGroup();
            $gruppe[$grpnr] = [
                'ANLID' => $row->getAnlage()->getAnlId(),
                'GMIN' => $row->getUnitFirst(),
                'GMAX' => $row->getUnitLast(),
                'GRPNR' => $row->getDcGroup(),
                'GroupName' => $row->getDcGroupName(),
            ];
        }

        return $gruppe;
    }

    public function getInvertersFromDcGroups(): array
    {
        $inverters = [];
        $groups = $this->getGroups();
        foreach ($groups as $key => $group) {
            for ($i = $group->getUnitFirst(); $i <= $group->getUnitLast(); ++$i) {
                $inverters[] = [
                    'inverterNo' => $i,
                    'group' => $group->getDcGroupName(),
                    'name' => "Inv. #$i",
                ];
            }
        }

        return $inverters;
    }

    public function getUseCosPhi(): ?bool
    {
        return $this->useCosPhi;
    }

    public function setUseCosPhi(bool $useCosPhi): self
    {
        $this->useCosPhi = $useCosPhi;

        return $this;
    }

    /**
     * Methode of PR calculation. <br>
     * no | without customer algorithm, use standard calculation
     * Groningen<br>
     * Veendamm<br>
     * Lelystad | with temp corr. <br>.
     *
     * @deprecated use getPrFormular2 instead
     */
    #[Deprecated]
    public function getUseCustPRAlgorithm(): ?string
    {
        return $this->prFormular2;
    }

    /**
     * @deprecated use setPrFormular2 instead
     */
    #[Deprecated]
    public function setUseCustPRAlgorithm(?string $useCustPRAlgorithm): self
    {
        $this->prFormular2 = $useCustPRAlgorithm;

        return $this;
    }

    public function getAnlagenReports(): Collection
    {
        return $this->anlagenReports;
    }

    public function getPldAlgorithm(): ?string
    {
        return $this->pldAlgorithm;
    }

    public function setPldAlgorithm(?string $pldAlgorithm): self
    {
        $this->pldAlgorithm = $pldAlgorithm;

        return $this;
    }

    public function addAnlagenReport(AnlagenReports $anlagenReport): self
    {
        if (!$this->anlagenReports->contains($anlagenReport)) {
            $this->anlagenReports[] = $anlagenReport;
            $anlagenReport->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlagenReport(AnlagenReports $anlagenReport): self
    {
        if ($this->anlagenReports->contains($anlagenReport)) {
            $this->anlagenReports->removeElement($anlagenReport);
            // set the owning side to null (unless already changed)
            if ($anlagenReport->getAnlage() === $this) {
                $anlagenReport->setAnlage(null);
            }
        }

        return $this;
    }

    public function getShowPR(): ?bool
    {
        return $this->showPR;
    }

    public function setShowPR(bool $showPR): self
    {
        $this->showPR = $showPR;

        return $this;
    }

    public function getOpenWeather()
    {
        $weatherArray = [];
        $apiKey = '795982a4e205f23abb3ce3cf9a9a032a';
        $lat = $this->anlGeoLat;
        $lng = $this->anlGeoLon;
        if ($lat and $lng) {
            $urli = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lng&lang=en&APPID=$apiKey";
            $contents = file_get_contents($urli);
            $clima = json_decode($contents, null, 512, JSON_THROW_ON_ERROR);
            if ($clima) {
                $weatherArray['tempC'] = round($clima->main->temp - 273.15, 0);
                $weatherArray['tempF'] = round((($clima->main->temp * 9) / 5) + 32, 0);
                $weatherArray['iconCountry'] = strtolower((string) $clima->sys->country);
                $weatherArray['iconWeather'] = 'https://openweathermap.org/img/w/'.strtolower((string) $clima->weather[0]->icon).'.png';
                $weatherArray['description'] = @$clima->weather[0]->description;
                $weatherArray['cityName'] = @$clima->name;

                return $weatherArray;
            }
        }

        return false;
    }

    public function getEventMails(): Collection
    {
        return $this->eventMails;
    }

    public function addEventMail(AnlageEventMail $eventMail): self
    {
        if (!$this->eventMails->contains($eventMail)) {
            $this->eventMails[] = $eventMail;
            $eventMail->setAnlage($this);
        }

        return $this;
    }

    public function removeEventMail(AnlageEventMail $eventMail): self
    {
        if ($this->eventMails->contains($eventMail)) {
            $this->eventMails->removeElement($eventMail);
            // set the owning side to null (unless already changed)
            if ($eventMail->getAnlage() === $this) {
                $eventMail->setAnlage(null);
            }
        }

        return $this;
    }

    public function getIrrLimitAvailability(): ?string
    {
        return $this->irrLimitAvailability;
    }

    public function setIrrLimitAvailability(string $irrLimitAvailability): self
    {
        $this->irrLimitAvailability = $irrLimitAvailability;

        return $this;
    }

    public function getContractualAvailability(): ?string
    {
        return $this->contractualAvailability;
    }

    public function setContractualAvailability(string $contractualAvailability): self
    {
        $this->contractualAvailability = str_replace(',', '.', $contractualAvailability);

        return $this;
    }

    public function getContractualPR(): ?float
    {
        return (float) $this->contractualPR;
    }

    public function setContractualPR(string $contractualPR): self
    {
        $this->contractualPR = str_replace(',', '.', $contractualPR);

        return $this;
    }

    public function getContractualPower(): ?float
    {
        return (float) $this->contractualPower;
    }

    public function getGuaranteedExpectedEnergy($expectedEnergy): float
    {
        return $expectedEnergy * (1 - ($this->getTransformerTee() / 100)) * (1 - ($this->getGuaranteeTee() / 100));
    }

    public function getContractualGuarantiedPower(): float
    {
        $factor = 1 - $this->getGuaranteeTee() / 100 - $this->getTransformerTee() / 100;

        return (float) $this->contractualPower * $factor;
    }

    public function setContractualPower(string $contractualPower): self
    {
        $this->contractualPower = str_replace(',', '.', $contractualPower);

        return $this;
    }

    public function getAnlageCase5s(): Collection
    {
        return $this->anlageCase5s;
    }

    public function getAnlageCase5sDate($date): Collection
    {
        $criteria = AnlagenRepository::case5ByDateCriteria($date);

        return $this->anlageCase5s->matching($criteria);
    }

    public function addAnlageCase5(AnlageCase5 $anlageCase5): self
    {
        if (!$this->anlageCase5s->contains($anlageCase5)) {
            $this->anlageCase5s[] = $anlageCase5;
            $anlageCase5->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageCase5(AnlageCase5 $anlageCase5): self
    {
        if ($this->anlageCase5s->contains($anlageCase5)) {
            $this->anlageCase5s->removeElement($anlageCase5);
            // set the owning side to null (unless already changed)
            if ($anlageCase5->getAnlage() === $this) {
                $anlageCase5->setAnlage(null);
            }
        }

        return $this;
    }

    public function getAnlageCase6s(): Collection
    {
        return $this->anlageCase6s;
    }

    public function getAnlageCase6sDate($date): Collection
    {
        $criteria = AnlagenRepository::case6ByDateCriteria($date);

        return $this->anlageCase5s->matching($criteria);
    }

    public function addAnlageCase6(AnlageCase6 $anlageCase6): self
    {
        if (!$this->anlageCase6s->contains($anlageCase6)) {
            $this->anlageCase6s[] = $anlageCase6;
            $anlageCase6->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageCase6(AnlageCase6 $anlageCase6): self
    {
        if ($this->anlageCase6s->contains($anlageCase6)) {
            $this->anlageCase6s->removeElement($anlageCase6);
            // set the owning side to null (unless already changed)
            if ($anlageCase6->getAnlage() === $this) {
                $anlageCase6->setAnlage(null);
            }
        }

        return $this;
    }

    public function getAnlagePVSystDatens(): Collection
    {
        return $this->anlagePVSystDatens;
    }

    public function addAnlagePVSystDaten(AnlagePVSystDaten $anlagePVSystDaten): self
    {
        if (!$this->anlagePVSystDatens->contains($anlagePVSystDaten)) {
            $this->anlagePVSystDatens[] = $anlagePVSystDaten;
            $anlagePVSystDaten->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlagePVSystDaten(AnlagePVSystDaten $anlagePVSystDaten): self
    {
        if ($this->anlagePVSystDatens->removeElement($anlagePVSystDaten)) {
            // set the owning side to null (unless already changed)
            if ($anlagePVSystDaten->getAnlage() === $this) {
                $anlagePVSystDaten->setAnlage(null);
            }
        }

        return $this;
    }

    public function getShowPvSyst(): ?bool
    {
        return $this->showPvSyst;
    }

    public function setShowPvSyst(bool $showPvSyst): self
    {
        $this->showPvSyst = $showPvSyst;

        return $this;
    }

    public function getWeatherStation(): ?weatherStation
    {
        return $this->weatherStation;
    }

    public function setWeatherStation(?WeatherStation $weatherStation): self
    {
        $this->weatherStation = $weatherStation;

        return $this;
    }

    public function getPacDate(): ?DateTime
    {
        return $this->pacDate;
    }

    public function setPacDate(?DateTime $pacDate): self
    {
        $this->pacDate = $pacDate;

        return $this;
    }

    public function getFacDate(): ?DateTime
    {
        return $this->facDate;
    }

    public function setFacDate(?DateTime $facDate): self
    {
        $this->facDate = $facDate;

        return $this;
    }

    public function getUsePac(): ?bool
    {
        return $this->usePac;
    }

    public function setUsePac(bool $usePac): self
    {
        $this->usePac = $usePac;

        return $this;
    }

    public function getAnlageForecasts(): Collection
    {
        return $this->anlageForecasts;
    }

    public function addAnlageForecast(AnlageForcast $anlageForecast): self
    {
        if (!$this->anlageForecasts->contains($anlageForecast)) {
            $this->anlageForecasts[] = $anlageForecast;
            $anlageForecast->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageForecast(AnlageForcast $anlageForecast): self
    {
        if ($this->anlageForecasts->removeElement($anlageForecast)) {
            // set the owning side to null (unless already changed)
            if ($anlageForecast->getAnlage() === $this) {
                $anlageForecast->setAnlage(null);
            }
        }

        return $this;
    }

    public function getAnlageForecastDays(): Collection
    {
        return $this->anlageForecastDays;
    }

    public function addAnlageForecastDay(AnlageForcastDay $anlageForecastDay): self
    {
        if (!$this->anlageForecastDays->contains($anlageForecastDay)) {
            $this->anlageForecastDays[] = $anlageForecastDay;
            $anlageForecastDay->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageForecastDay(AnlageForcastDay $anlageForecastDay): self
    {
        if ($this->anlageForecastDays->removeElement($anlageForecastDay)) {
            // set the owning side to null (unless already changed)
            if ($anlageForecastDay->getAnlage() === $this) {
                $anlageForecastDay->setAnlage(null);
            }
        }

        return $this;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(AnlageGroups $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
            $group->setAnlage($this);
        }

        return $this;
    }

    public function removeGroup(AnlageGroups $group): self
    {
        if ($this->groups->removeElement($group)) {
            // set the owning side to null (unless already changed)
            if ($group->getAnlage() === $this) {
                $group->setAnlage(null);
            }
        }

        return $this;
    }

    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(AnlageModules $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules[] = $module;
            $module->setAnlage($this);
        }

        return $this;
    }

    public function removeModule(AnlageModules $module): self
    {
        if ($this->modules->removeElement($module)) {
            // set the owning side to null (unless already changed)
            if ($module->getAnlage() === $this) {
                $module->setAnlage(null);
            }
        }

        return $this;
    }

    public function getIsOstWestAnlage(): ?bool
    {
        return $this->isOstWestAnlage;
    }

    public function setIsOstWestAnlage(bool $isOstWestAnlage): self
    {
        $this->isOstWestAnlage = $isOstWestAnlage;

        return $this;
    }

    #[Deprecated]
    public function getMinIrradiationAvailability(): ?string
    {
        return $this->threshold2PA2;
    }
    #[Deprecated]
    public function setMinIrradiationAvailability(?string $minIrradiationAvailability): self
    {
        $this->threshold2PA2 = str_replace(',', '.', $minIrradiationAvailability);
        return $this;
    }
    #[Deprecated]
    public function getThreshold1PA(): ?float
    {
        return (float)$this->threshold1PA2;
    }
    #[Deprecated]
    public function setThreshold1PA(?string $threshold1PA): self
    {
        $this->threshold1PA2 = str_replace(',', '.', $threshold1PA);
        return $this;
    }
    #[Deprecated]
    public function getThreshold2PA(): ?float
    {
        return (float)$this->threshold2PA2;
    }
    #[Deprecated]
    public function setThreshold2PA(?string $threshold2PA): self
    {
        $this->threshold2PA2 = str_replace(',', '.', $threshold2PA);
        return $this;
    }

    public function getThreshold1PA0(): ?float
    {
        return (float)$this->threshold1PA0 ?? 0;
    }

    public function setThreshold1PA0(?string $threshold1PA0): self
    {
        $this->threshold1PA0 = str_replace(',', '.',$threshold1PA0);
        return $this;
    }

    public function getThreshold1PA1(): ?float
    {
        return (float)$this->threshold1PA1 ?? 0;
    }

    public function setThreshold1PA1(?string $threshold1PA1): self
    {
        $this->threshold1PA1 = str_replace(',', '.',$threshold1PA1);
        return $this;
    }

    public function getThreshold1PA2(): ?float
    {
        return (float)$this->threshold1PA2 ?? 0;
    }

    public function setThreshold1PA2(?string $threshold1PA2): self
    {
        $this->threshold1PA2 = str_replace(',', '.',$threshold1PA2);
        return $this;
    }

    public function getThreshold1PA3(): ?float
    {
        return (float)$this->threshold1PA3 ?? 0;
    }

    public function setThreshold1PA3(?string $threshold1PA3): self
    {
        $this->threshold1PA3 = str_replace(',', '.',$threshold1PA3);
        return $this;
    }

    public function getThreshold2PA0(): ?float
    {
        return (float)$this->threshold2PA0 ?? 50;
    }

    public function setThreshold2PA0(?string $threshold2PA0): self
    {
        $this->threshold2PA0 = str_replace(',', '.',$threshold2PA0);
        return $this;
    }

    public function getThreshold2PA1(): ?float
    {
        return (float)$this->threshold2PA1 ?? 50;
    }

    public function setThreshold2PA1(?string $threshold2PA1): self
    {
        $this->threshold2PA1 = str_replace(',', '.',$threshold2PA1);
        return $this;
    }

    public function getThreshold2PA2(): ?float
    {
        return (float)$this->threshold2PA2 ?? 50;
    }

    public function setThreshold2PA2(?string $threshold2PA2): self
    {
        $this->threshold2PA2 = str_replace(',', '.',$threshold2PA2);
        return $this;
    }

    public function getThreshold2PA3(): ?float
    {
        return (float)$this->threshold2PA3 ?? 50;
    }

    public function setThreshold2PA3(?string $threshold2PA3): self
    {
        $this->threshold2PA3 = str_replace(',', '.',$threshold2PA3);
        return $this;
    }

    public function isUsePAFlag0(): bool
    {
        return $this->usePAFlag0;
    }

    public function setUsePAFlag0(bool $usePAFlag0): void
    {
        $this->usePAFlag0 = $usePAFlag0;
    }

    public function isUsePAFlag1(): bool
    {
        return $this->usePAFlag1;
    }

    public function setUsePAFlag1(bool $usePAFlag1): void
    {
        $this->usePAFlag1 = $usePAFlag1;
    }

    public function isUsePAFlag2(): bool
    {
        return $this->usePAFlag2;
    }

    public function setUsePAFlag2(bool $usePAFlag2): void
    {
        $this->usePAFlag2 = $usePAFlag2;
    }

    public function isUsePAFlag3(): bool
    {
        return $this->usePAFlag3;
    }

    public function setUsePAFlag3(bool $usePAFlag3): void
    {
        $this->usePAFlag3 = $usePAFlag3;
    }



    public function getPaFormular0(): ?string
    {
        if ($this->paFormular0 === null) return 1;
        return $this->paFormular0;
    }

    public function setPaFormular0(?string $paFormular0): self
    {
        $this->paFormular0 = $paFormular0;
        return $this;
    }

    public function getPaFormular1(): ?string
    {
        if ($this->paFormular1 === null) return 1;
        return $this->paFormular1;
    }

    public function setPaFormular1(?string $paFormular1): self
    {
        $this->paFormular1 = $paFormular1;
        return $this;
    }

    public function getPaFormular2(): ?string
    {
        if ($this->paFormular2 === null) return 1;
        return $this->paFormular2;
    }

    public function setPaFormular2(?string $paFormular2): self
    {
        $this->paFormular2 = $paFormular2;
        return $this;
    }

    public function getPaFormular3(): ?string
    {
        if ($this->paFormular3 === null) return 1;
        return $this->paFormular3;
    }

    public function setPaFormular3(?string $paFormular3): self
    {
        $this->paFormular3 = $paFormular3;
        return $this;
    }

    public function getPrFormular0(): ?string
    {
        return $this->prFormular0;
    }

    public function setPrFormular0(?string $prFormular0): self
    {
        $this->prFormular0 = $prFormular0;
        return $this;
    }

    public function getPrFormular1(): ?string
    {
        return $this->prFormular1;
    }

    public function setPrFormular1(?string $prFormular1): self
    {
        $this->prFormular1 = $prFormular1;
        return $this;
    }

    public function getPrFormular2(): ?string
    {
        return $this->prFormular2;
    }

    public function setPrFormular2(?string $prFormular2): self
    {
        $this->prFormular2 = $prFormular2;
        return $this;
    }

    public function getPrFormular3(): ?string
    {
        return $this->prFormular3;
    }

    public function setPrFormular3(?string $prFormular3): self
    {
        $this->prFormular3 = $prFormular3;
        return $this;
    }



    public function getTimesConfigs(): Collection
    {
        return $this->timesConfigs;
    }

    public function addTimesConfig(TimesConfig $timesConfig): self
    {
        if (!$this->timesConfigs->contains($timesConfig)) {
            $this->timesConfigs[] = $timesConfig;
            $timesConfig->setAnlage($this);
        }

        return $this;
    }

    public function removeTimesConfig(TimesConfig $timesConfig): self
    {
        if ($this->timesConfigs->removeElement($timesConfig)) {
            // set the owning side to null (unless already changed)
            if ($timesConfig->getAnlage() === $this) {
                $timesConfig->setAnlage(null);
            }
        }

        return $this;
    }

    public function getShowForecast(): ?bool
    {
        return $this->showForecast;
    }

    public function setShowForecast(bool $showForecast): self
    {
        $this->showForecast = $showForecast;

        return $this;
    }

    public function getAnlageGridMeterDays(): Collection
    {
        return $this->anlageGridMeterDays;
    }

    public function addAnlageGridMeterDay(AnlageGridMeterDay $anlageGridMeterDay): self
    {
        if (!$this->anlageGridMeterDays->contains($anlageGridMeterDay)) {
            $this->anlageGridMeterDays[] = $anlageGridMeterDay;
            $anlageGridMeterDay->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageGridMeterDay(AnlageGridMeterDay $anlageGridMeterDay): self
    {
        if ($this->anlageGridMeterDays->removeElement($anlageGridMeterDay)) {
            // set the owning side to null (unless already changed)
            if ($anlageGridMeterDay->getAnlage() === $this) {
                $anlageGridMeterDay->setAnlage(null);
            }
        }

        return $this;
    }

    public function getUseGridMeterDayData(): ?bool
    {
        return $this->useGridMeterDayData;
    }

    public function setUseGridMeterDayData(bool $useGridMeterDayData): self
    {
        $this->useGridMeterDayData = $useGridMeterDayData;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = strtolower($country);

        return $this;
    }

    /**
     * @return Collection|OpenWeather
     */
    public function getLastOpenWeather(): Collection
    {
        $criteria = AnlagenRepository::lastOpenWeatherCriteria();

        return $this->openWeather->matching($criteria);
    }

    public function addOpenWeather(OpenWeather $openWeather): self
    {
        if (!$this->openWeather->contains($openWeather)) {
            $this->openWeather[] = $openWeather;
            $openWeather->setAnlage($this);
        }

        return $this;
    }

    public function removeOpenWeather(OpenWeather $openWeather): self
    {
        if ($this->openWeather->removeElement($openWeather)) {
            // set the owning side to null (unless already changed)
            if ($openWeather->getAnlage() === $this) {
                $openWeather->setAnlage(null);
            }
        }

        return $this;
    }

    public function getCalcPR(): ?bool
    {
        return $this->calcPR;
    }

    public function setCalcPR(bool $calcPR): self
    {
        $this->calcPR = $calcPR;

        return $this;
    }

    public function getPacDuration(): ?string
    {
        return $this->pacDuration;
    }

    public function setPacDuration(string $pacDuration): self
    {
        $this->pacDuration = $pacDuration;

        return $this;
    }

    public function getKwPeakPvSyst(): ?float
    {
        return (float) $this->kwPeakPvSyst;
    }

    public function setKwPeakPvSyst(?string $kwPeakPvSyst): self
    {
        $this->kwPeakPvSyst = $kwPeakPvSyst;

        return $this;
    }

    public function getKwPeakPLDCalculation(): ?float
    {
        return (float) $this->kwPeakPLDCalculation;
    }

    public function setKwPeakPLDCalculation(?string $kwPeakPLDCalculation): self
    {
        $this->kwPeakPLDCalculation = $kwPeakPLDCalculation;
        return $this;
    }

    public function getDesignPR(): ?float
    {
        return (float) $this->designPR;
    }

    public function setDesignPR(?string $designPR): self
    {
        $this->designPR = $designPR;

        return $this;
    }

    public function getFacDateStart(): ?DateTime
    {
        return $this->facDateStart;
    }

    public function setFacDateStart(?DateTime $facDateStart): self
    {
        $this->facDateStart = $facDateStart;

        return $this;
    }

    public function getPacDateEnd(): ?DateTime
    {
        return $this->pacDateEnd;
    }

    public function setPacDateEnd(?DateTime $pacDateEnd): self
    {
        $this->pacDateEnd = $pacDateEnd;

        return $this;
    }

    /**
     * @return Collection|AnlagenPvSystMonth[]
     */
    public function getPvSystMonths(): Collection
    {
        return $this->anlagenPvSystMonths;
    }

    public function getPvSystMonthsArray(): array
    {
        $array = [];
        /** @var AnlagenPvSystMonth $month */
        foreach ($this->getPvSystMonths() as $month) {
            $array[$month->getMonth()] = [
                'prDesign' => $month->getPrDesign(),
                'ertragDesign' => $month->getErtragDesign(),
                'irrDesign' => $month->getIrrDesign(),
                'tempAmbDesign' => $month->getTempAmbientDesign(),
                'tempAmbWeightedDesign' => $month->getTempArrayAvgDesign(),
            ];
        }

        return $array;
    }

    public function addPvSystMonth(AnlagenPvSystMonth $anlagenPvSystMonth): self
    {
        if (!$this->anlagenPvSystMonths->contains($anlagenPvSystMonth)) {
            $this->anlagenPvSystMonths[] = $anlagenPvSystMonth;
            $anlagenPvSystMonth->setAnlage($this);
        }

        return $this;
    }

    public function removePvSystMonth(AnlagenPvSystMonth $anlagenPvSystMonth): self
    {
        if ($this->anlagenPvSystMonths->removeElement($anlagenPvSystMonth)) {
            // set the owning side to null (unless already changed)
            if ($anlagenPvSystMonth->getAnlage() === $this) {
                $anlagenPvSystMonth->setAnlage(null);
            }
        }

        return $this;
    }

    public function getOneMonthPvSyst($month): ?AnlagenPvSystMonth
    {
        $criteria = AnlagenRepository::oneMonthPvSystCriteria($month);
        $result = $this->anlagenPvSystMonths->matching($criteria);

        return $result[0];
    }

    public function getLid(): float
    {
        return (float) $this->lid;
    }

    public function setLid(string $lid): self
    {
        $this->lid = $lid;

        return $this;
    }

    public function getAnnualDegradation(): ?float
    {
        return (float) $this->annualDegradation;
    }

    public function setAnnualDegradation(?string $annualDegradation): self
    {
        $this->annualDegradation = $annualDegradation;

        return $this;
    }

    public function getPldPR(): ?float
    {
        return (float) $this->pldPR;
    }

    public function setPldPR(?string $pldPR): self
    {
        $this->pldPR = $pldPR;

        return $this;
    }
    public function isTreatingDataGapsAsOutage(): ?bool
    {
        return $this->treatingDataGapsAsOutage;
    }
    public function getTreatingDataGapsAsOutage(): ?bool
    {
        return $this->treatingDataGapsAsOutage;
    }

    public function setTreatingDataGapsAsOutage(?bool $treatingDataGapsAsOutage): void
    {
        $this->treatingDataGapsAsOutage = $treatingDataGapsAsOutage;
    }

    public function getEpcReportType(): ?string
    {
        return $this->epcReportType;
    }

    public function setEpcReportType(string $epcReportType): self
    {
        $this->epcReportType = $epcReportType;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getMonthlyYields(): Collection
    {
        return $this->anlagenMonthlyData;
    }

    /**
     * @return Collection
     */
    public function getAnlagenMonthlyData(): Collection
    {
        return $this->anlagenMonthlyData;
    }

    public function addMonthlyYield(AnlagenMonthlyData $anlagenMonthlyData): self
    {
        if (!$this->anlagenMonthlyData->contains($anlagenMonthlyData)) {
            $this->anlagenMonthlyData[] = $anlagenMonthlyData;
            $anlagenMonthlyData->setAnlage($this);
        }

        return $this;
    }

    public function removeMonthlyYield(AnlagenMonthlyData $anlagenMonthlyData): self
    {
        if ($this->anlagenMonthlyData->removeElement($anlagenMonthlyData)) {
            // set the owning side to null (unless already changed)
            if ($anlagenMonthlyData->getAnlage() === $this) {
                $anlagenMonthlyData->setAnlage(null);
            }
        }

        return $this;
    }

    public function getTransformerTee(): ?float
    {
        return (float) $this->transformerTee;
    }

    public function setTransformerTee(string $transformerTee): self
    {
        $this->transformerTee = $transformerTee;

        return $this;
    }

    public function getGuaranteeTee(): ?float
    {
        return (float) $this->guaranteeTee;
    }

    public function setGuaranteeTee(string $guaranteeTee): self
    {
        $this->guaranteeTee = $guaranteeTee;

        return $this;
    }

    public function getUsePnomForPld(): bool
    {
        return $this->usePnomForPld;
    }

    public function isUsePnomForPld(): bool
    {
        return $this->usePnomForPld;
    }

    public function setUsePnomForPld(bool $usePnomForPld): self
    {
        $this->usePnomForPld = $usePnomForPld;
        return $this;
    }

    public function getPldYield(): ?float
    {
        return (float) $this->pldYield;
    }

    public function setPldYield(string $pldYield): self
    {
        $this->pldYield = $pldYield;

        return $this;
    }

    public function getProjektNr(): ?string
    {
        return $this->projektNr;
    }

    public function setProjektNr(string $projektNr): self
    {
        $this->projektNr = $projektNr;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getLegendEpcReports(): Collection
    {
        $criteria = AnlagenRepository::selectLegendType('epc');

        return $this->anlageLegendReports->matching($criteria);
    }

    public function addLegendEpcReport(AnlageLegendReport $anlageLegendReport): self
    {
        return $this->addAnlageLegendReport($anlageLegendReport);
    }

    public function removeLegendEpcReport(AnlageLegendReport $anlageLegendReport): self
    {
        return $this->removeAnlageLegendReport($anlageLegendReport);
    }

    /**
     * @return Collection
     */
    public function getLegendMonthlyReports(): Collection
    {
        $criteria = AnlagenRepository::selectLegendType('monthly');

        return $this->anlageLegendReports->matching($criteria);
    }

    public function addLegendMonthlyReport(AnlageLegendReport $anlageLegendReport): self
    {
        return $this->addAnlageLegendReport($anlageLegendReport);
    }

    public function removeLegendMonthlyReport(AnlageLegendReport $anlageLegendReport): self
    {
        return $this->removeAnlageLegendReport($anlageLegendReport);
    }

    /**
     * @return Collection
     */
    public function getAnlageLegendReports(): Collection
    {
        return $this->anlageLegendReports;
    }

    public function addAnlageLegendReport(AnlageLegendReport $anlageLegendReport): self
    {
        if (!$this->anlageLegendReports->contains($anlageLegendReport)) {
            $this->anlageLegendReports[] = $anlageLegendReport;
            $anlageLegendReport->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageLegendReport(AnlageLegendReport $anlageLegendReport): self
    {
        if ($this->anlageLegendReports->removeElement($anlageLegendReport)) {
            // set the owning side to null (unless already changed)
            if ($anlageLegendReport->getAnlage() === $this) {
                $anlageLegendReport->setAnlage(null);
            }
        }

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->Notes;
    }

    public function setNotes(?string $Notes): self
    {
        $this->Notes = $Notes;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAnlageMonth(): Collection
    {
        return $this->anlageMonth;
    }

    public function addAnlageMonth(AnlageMonth $anlageMonth): self
    {
        if (!$this->anlageMonth->contains($anlageMonth)) {
            $this->anlageMonth[] = $anlageMonth;
            $anlageMonth->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageMonth(AnlageMonth $anlageMonth): self
    {
        if ($this->anlageMonth->removeElement($anlageMonth)) {
            // set the owning side to null (unless already changed)
            if ($anlageMonth->getAnlage() === $this) {
                $anlageMonth->setAnlage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getInverters(): Collection
    {
        return $this->Inverters;
    }

    public function addInverter(AnlageInverters $inverter): self
    {
        if (!$this->Inverters->contains($inverter)) {
            $this->Inverters[] = $inverter;
            $inverter->setAnlage($this);
        }

        return $this;
    }

    public function removeInverter(AnlageInverters $inverter): self
    {
        if ($this->Inverters->removeElement($inverter)) {
            // set the owning side to null (unless already changed)
            if ($inverter->getAnlage() === $this) {
                $inverter->setAnlage(null);
            }
        }

        return $this;
    }

    public function getTempCorrCellTypeAvg(): ?string
    {
        return $this->tempCorrCellTypeAvg;
    }

    public function setTempCorrCellTypeAvg(string $tempCorrCellTypeAvg): self
    {
        $this->tempCorrCellTypeAvg = $tempCorrCellTypeAvg;

        return $this;
    }

    public function getTempCorrGamma(): ?string
    {
        return $this->tempCorrGamma;
    }

    public function setTempCorrGamma(string $tempCorrGamma): self
    {
        $this->tempCorrGamma = $tempCorrGamma;

        return $this;
    }

    public function getTempCorrA(): ?string
    {
        return $this->tempCorrA;
    }

    public function setTempCorrA(string $tempCorrA): self
    {
        $this->tempCorrA = $tempCorrA;

        return $this;
    }

    public function getTempCorrB(): ?string
    {
        return $this->tempCorrB;
    }

    public function setTempCorrB(string $tempCorrB): self
    {
        $this->tempCorrB = $tempCorrB;

        return $this;
    }

    public function getTempCorrDeltaTCnd(): ?string
    {
        return $this->tempCorrDeltaTCnd;
    }

    public function setTempCorrDeltaTCnd(string $tempCorrDeltaTCnd): self
    {
        $this->tempCorrDeltaTCnd = $tempCorrDeltaTCnd;

        return $this;
    }

    public function getDegradationPR(): float
    {
        return (float)$this->degradationPR;
    }

    public function setDegradationPR(string $degradationPR): void
    {
        $this->degradationPR = $degradationPR;
    }


    public function getPldNPValue(): ?string
    {
        return $this->pldNPValue;
    }

    public function setPldNPValue(string $pldNPValue): self
    {
        $this->pldNPValue = $pldNPValue;

        return $this;
    }

    public function getPldDivisor(): ?string
    {
        return $this->pldDivisor;
    }

    public function setPldDivisor(string $pldDivisor): self
    {
        $this->pldDivisor = $pldDivisor;

        return $this;
    }

    public function getEpcReportStart(): ?DateTime
    {
        return $this->epcReportStart;
    }

    public function setEpcReportStart(?DateTime $epcReportStart): self
    {
        $this->epcReportStart = $epcReportStart;

        return $this;
    }

    public function getEpcReportEnd(): ?DateTime
    {
        return $this->epcReportEnd;
    }

    public function setEpcReportEnd(?DateTime $epcReportEnd): self
    {
        $this->epcReportEnd = $epcReportEnd;

        return $this;
    }

    public function getInverterStartVoltage(): ?string
    {
        return $this->inverterStartVoltage;
    }

    public function setInverterStartVoltage(string $inverterStartVoltage): self
    {
        $this->inverterStartVoltage = $inverterStartVoltage;

        return $this;
    }

    public function getUseLowerIrrForExpected(): ?bool
    {
        return $this->useLowerIrrForExpected;
    }

    public function setUseLowerIrrForExpected(bool $useLowerIrrForExpected): self
    {
        $this->useLowerIrrForExpected = $useLowerIrrForExpected;

        return $this;
    }

    public function getEpcReportNote(): ?string
    {
        return $this->epcReportNote;
    }

    public function setEpcReportNote(string $epcReportNote): self
    {
        $this->epcReportNote = $epcReportNote;

        return $this;
    }

    public function getConfigType(): ?int
    {
        return $this->configType;
    }

    public function setConfigType(int $configType): self
    {
        $this->configType = $configType;

        return $this;
    }

    public function getShowInverterOutDiag(): ?bool
    {
        return $this->showInverterOutDiag;
    }

    public function setShowInverterOutDiag(bool $showInverterOutDiag): self
    {
        $this->showInverterOutDiag = $showInverterOutDiag;

        return $this;
    }

    /**
     * @return Collection|Log[]
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setAnlage($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getAnlage() === $this) {
                $log->setAnlage(null);
            }
        }

        return $this;
    }

    public function getDbAnlagenData(): string
    {
        return $this->dbAnlagenData;
    }

    public function getHasDc(): ?bool
    {
        return $this->hasDc;
    }

    public function setHasDc(bool $hasDc): self
    {
        $this->hasDc = $hasDc;

        return $this;
    }

    public function getHasStrings(): ?bool
    {
        return $this->hasStrings;
    }

    public function setHasStrings(bool $hasStrings): self
    {
        $this->hasStrings = $hasStrings;

        return $this;
    }

    public function getHasPPC(): ?bool
    {
        return $this->hasPPC;
    }

    public function setHasPPC(bool $hasPPC): self
    {
        $this->hasPPC = $hasPPC;

        return $this;
    }

    public function getUsePPC(): ?bool
    {
        return $this->usePPC;
    }

    public function setUsePPC(bool $usePPC): self
    {
        $this->usePPC = $usePPC;

        return $this;
    }

    public function getHasPannelTemp(): ?bool
    {
        return $this->hasPannelTemp;
    }

    public function setHasPannelTemp(bool $hasPannelTemp): self
    {
        $this->hasPannelTemp = $hasPannelTemp;

        return $this;
    }

    /**
     * @return Collection|Ticket[]
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): self
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets[] = $ticket;
            $ticket->setAnlage($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): self
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getAnlage() === $this) {
                $ticket->setAnlage(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {

        return $this->getAnlId() ;
    }

    public function getEconomicVarNames(): ?EconomicVarNames
    {
        return $this->economicVarNames;
    }

    public function setEconomicVarNames(?EconomicVarNames $economicVarNames): self
    {
        $this->economicVarNames = $economicVarNames;

        return $this;
    }

    /**
     * @return Collection|EconomicVarValues[]
     */
    public function getEconomicVarValues(): Collection
    {
        return $this->economicVarValues;
    }

    public function addEconomicVarValue(EconomicVarValues $economicVarValue): self
    {
        if (!$this->economicVarValues->contains($economicVarValue)) {
            $this->economicVarValues[] = $economicVarValue;
            $economicVarValue->setAnlage($this);
        }

        return $this;
    }

    public function removeEconomicVarValue(EconomicVarValues $economicVarValue): self
    {
        if ($this->economicVarValues->removeElement($economicVarValue)) {
            // set the owning side to null (unless already changed)
            if ($economicVarValue->getAnlage() === $this) {
                $economicVarValue->setAnlage(null);
            }
        }

        return $this;
    }

    public function getSettings(): ?AnlageSettings
    {
        return $this->settings;
    }

    public function setSettings(?AnlageSettings $settings): self
    {
        // unset the owning side of the relation if necessary
        if ($settings === null && $this->settings !== null) {
            $this->settings->setAnlage(null);
        }

        // set the owning side of the relation if necessary
        if ($settings !== null && $settings->getAnlage() !== $this) {
            $settings->setAnlage($this);
        }

        $this->settings = $settings;

        return $this;
    }

    public function getUseDayForecast(): ?bool
    {
        return $this->useDayForecast;
    }

    public function setUseDayForecast(bool $useDayForecast): self
    {
        $this->useDayForecast = $useDayForecast;

        return $this;
    }

    public function getUseDayaheadForecast(): ?bool
    {
        return $this->useDayaheadForecast;
    }

    public function setUseDayaheadForecast(bool $useDayaheadForecast): self
    {
        $this->useDayaheadForecast = $useDayaheadForecast;

        return $this;
    }

    public function getHasSunshadingModel(): ?bool
    {
        return $this->hasSunshadingModel;
    }

    public function setHasSunshadingModel(bool $hasSunshadingModel): self
    {
        $this->hasSunshadingModel = $hasSunshadingModel;

        return $this;
    }

    public function getIsTrackerEow(): ?bool
    {
        return $this->isTrackerEow;
    }

    public function setIsTrackerEow(bool $isTrackerEow): self
    {
        $this->isTrackerEow = $isTrackerEow;

        return $this;
    }

    public function getDegradationForecast(): float
    {
        return (float) $this->degradationForecast;
    }

    public function setDegradationForecast(?string $degradationForecast): self
    {
        $this->degradationForecast = $degradationForecast;

        return $this;
    }

    public function getLossesForecast(): float
    {
        return (float) $this->lossesForecast;
    }

    public function setLossesForecast(?string $lossesForecast): self
    {
        $this->lossesForecast = $lossesForecast;

        return $this;
    }

    /**
     * @return Collection|AnlageFile[]
     */
    public function getAnlageFiles(): Collection
    {
        return $this->anlageFiles;
    }

    public function addAnlageFile(AnlageFile $anlageFile): self
    {
        if (!$this->anlageFiles->contains($anlageFile)) {
            $this->anlageFiles[] = $anlageFile;
            $anlageFile->setPlant($this);
        }

        return $this;
    }

    public function removeAnlageFile(AnlageFile $anlageFile): self
    {
        if ($this->anlageFiles->removeElement($anlageFile)) {
            // set the owning side to null (unless already changed)
            if ($anlageFile->getPlant() === $this) {
                $anlageFile->setPlant(null);
            }
        }

        return $this;
    }

    /**
     * we use this to determine whether a plant uses pvsys or not
     */
    public function hasPVSYST(): bool
    {
        return (intval($this->kwPeakPvSyst) > 0 ||  $this->showPvSyst);
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * @return Collection|Status[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    public function getHasWindSpeed(): ?bool
    {
        return $this->hasWindSpeed;
    }

    public function setHasWindSpeed(bool $hasWindSpeed): self
    {
        $this->hasWindSpeed = $hasWindSpeed;

        return $this;
    }

    public function hasGrid(): bool
    {
        return $this->showEvuDiag || $this->useGridMeterDayData;
    }

    public function getDataSourceAM(): ?string
    {
        return $this->DataSourceAM;
    }

    public function setDataSourceAM(?string $DataSourceAM): self
    {
        $this->DataSourceAM = $DataSourceAM;

        return $this;
    }

    public function getRetrieveAllData(): ?bool
    {
        return $this->RetrieveAllData;
    }

    public function setRetrieveAllData(bool $RetrieveAllData): self
    {
        $this->RetrieveAllData = $RetrieveAllData;

        return $this;
    }
    /**
     * MS 08/2023 sunShading
     *
     * @return Collection<int, AnlageSunShading>
     */
    public function getAnlageSunShading(): Collection
    {
           return $this->anlageSunShading;#sunShadingData

    }

    public function addAnlageSunShading(AnlageSunShading $anlageSunShading): self
    {
        if (!$this->anlageSunShading->contains($anlageSunShading)){
            $this->anlageSunShading[] = $anlageSunShading;
            $anlageSunShading->setAnlageId($this);
        }
        return $this;
    }

    public function removeAnlageSunShading(AnlageSunShading $anlageSunShading): self
    {
        if ($this->anlageSunShading->removeElement($anlageSunShading)) {
            // set the owning side to null (unless already changed)
            if ($anlageSunShading->getAnlageId() === $this) {
                $anlageSunShading->setAnlageId(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection<int, DayLightData>
     */
    public function getDayLightData(): Collection
    {
        return $this->dayLightData;
    }

    public function addDayLightData(DayLightData $dayLightData): self
    {
        if (!$this->dayLightData->contains($dayLightData)) {
            $this->dayLightData[] = $dayLightData;
            $dayLightData->setAnlage($this);
        }

        return $this;
    }

    public function removeDayLightData(DayLightData $dayLightData): self
    {
        if ($this->dayLightData->removeElement($dayLightData)) {
            // set the owning side to null (unless already changed)
            if ($dayLightData->getAnlage() === $this) {
                $dayLightData->setAnlage(null);
            }
        }

        return $this;
    }

    public function getFreqTolerance(): ?float
    {
        return (float) $this->freqTolerance;
    }

    public function setFreqTolerance(string $freqTolerance): self
    {
        $this->freqTolerance = $freqTolerance;

        return $this;
    }

    public function getFreqBase(): ?float
    {
        return (float) $this->freqBase;
    }

    public function setFreqBase(string $freqBase): self
    {
        $this->freqBase = $freqBase;

        return $this;
    }

    public function getHasFrequency(): ?bool
    {
        return $this->hasFrequency;
    }

    public function setHasFrequency(bool $hasFrequency): self
    {
        $this->hasFrequency = $hasFrequency;

        return $this;
    }

    /**
     * Function to calculate the Pnom for every inverter, returns a Array with the Pnom for all inverters.
     *
     * return array: Index = Inverter, value = Pnom of this Inverter
     * @throws InvalidArgumentException
     */
    public function getPnomInverterArray(): array
    {
        $dcPNomPerInvereter = [];

        switch ($this->getConfigType()) {
            case 1:
            case 2:
                foreach ($this->getGroups() as $inverter) {
                    $sumPNom = 0;
                    foreach ($inverter->getModules() as $module) {
                        $sumPNom += $module->getNumStringsPerUnit() * $module->getNumModulesPerString() * $module->getModuleType()->getPower() / 1000;
                    }
                    $dcPNomPerInvereter[$inverter->getDcGroup()] = $sumPNom;
                }
                break;
            case 3:
            case 4:
                foreach ($this->getAcGroups() as $inverter) {
                    $dcPNomPerInvereter[$inverter->getAcGroup()] = 0;
                }
                foreach ($this->getGroups() as $groups) {
                    $sumPNom = 0;
                    foreach ($groups->getModules() as $module) {
                        $sumPNom += $module->getNumStringsPerUnit() * $module->getNumModulesPerString() * $module->getModuleType()->getPower() / 1000;
                    }
                    $dcPNomPerInvereter[$groups->getAcGroup()] += $sumPNom * ($groups->getUnitLast() - $groups->getUnitFirst() + 1);
                }
                break;
        }

        return $dcPNomPerInvereter;
    }

    public function getPnomControlSum(): float
    {
        $controlSumPNom = 0;

        switch ($this->getConfigType()) {
            case 1:
            case 2:
                foreach ($this->getGroups() as $inverter) {
                    $sumPNom = 0;
                    foreach ($inverter->getModules() as $module) {
                        $sumPNom += $module->getNumStringsPerUnit() * $module->getNumModulesPerString() * $module->getModuleType()->getPower() / 1000;
                    }
                    $controlSumPNom += $sumPNom;
                }
                break;
            case 3:
            case 4:
                foreach ($this->getGroups() as $groups) {
                    $sumPNom = 0;
                    foreach ($groups->getModules() as $module) {
                        $sumPNom += $module->getNumStringsPerUnit() * $module->getNumModulesPerString() * $module->getModuleType()->getPower() / 1000;
                    }
                    $controlSumPNom += $sumPNom;
                }
                break;
        }

        return $controlSumPNom;
    }

    public function isExcludeFromExpCalc(): ?bool
    {
        return $this->excludeFromExpCalc;
    }

    public function setExcludeFromExpCalc(?bool $excludeFromExpCalc): self
    {
        $this->excludeFromExpCalc = $excludeFromExpCalc;

        return $this;
    }

    public function isUseAcGroupsAsSection(): ?bool
    {
        return $this->useAcGroupsAsSection;
    }

    public function setUseAcGroupsAsSection(?bool $useAcGroupsAsSection): self
    {
        $this->useAcGroupsAsSection = $useAcGroupsAsSection;

        return $this;
    }

    public function isIgnoreNegativEvu(): ?bool
    {
        return $this->ignoreNegativEvu;
    }

    public function getIgnoreNegativEvu(): ?bool
    {
        return $this->ignoreNegativEvu;
    }

    public function setIgnoreNegativEvu(bool $ignoreNegativEvu): self
    {
        $this->ignoreNegativEvu = $ignoreNegativEvu;

        return $this;
    }

  public function getFildForcastDat() {
        return $this->getDatFilename();
  }
    public function isDay(?DateTime $stamp = null): bool
    {
        if (!$stamp) $stamp = new DateTime();
        $sunrisedata = date_sun_info($stamp->getTimestamp(), (float) $this->getAnlGeoLat(), (float) $this->getAnlGeoLon());

        // ToDo: add some code to respect different timezones
        /*
        $offsetServer = new \DateTimeZone("Europe/Luxembourg");
        $plantoffset = new \DateTimeZone($this->getNearestTimezone($this->getAnlGeoLat(), $$this->getAnlGeoLon()));
        $totalOffset = $plantoffset->getOffset(new DateTime("now")) - $offsetServer->getOffset(new DateTime("now"));
        $returnArray['sunrise'] = $time.' '.date('H:i', $sunrisedata['sunrise'] + (int)$totalOffset);
        $returnArray['sunset'] = $time.' '.date('H:i', $sunrisedata['sunset'] + (int)$totalOffset);
        */

        $sunrise = date_create(date("Y-m-d H:i:s", $sunrisedata['sunrise']));
        $sunset = date_create(date("Y-m-d H:i:s", $sunrisedata['sunset']));

        return ($sunrise < $stamp && $stamp < $sunset);
    }

    public function isNight(?DateTime $stamp = null): bool
    {
        return !$this->isDay($stamp);
    }

    public function isExpectedTicket(): ?bool
    {
        return $this->expectedTicket;
    }

    public function setExpectedTicket(?bool $expectedTicket): self
    {
        $this->expectedTicket = $expectedTicket;

        return $this;
    }

    public function getPercentageDiff(): ?string
    {
        return $this->percentageDiff;
    }

    public function setPercentageDiff(?string $percentageDiff): self
    {
        $this->percentageDiff = $percentageDiff;

        return $this;
    }

    public function isWeatherTicket(): ?bool
    {
        return $this->weatherTicket;
    }

    public function setWeatherTicket(?bool $weatherTicket): self
    {
        $this->weatherTicket = $weatherTicket;

        return $this;
    }

    public function isActivateTicketSystem(): ?bool
    {
        return $this->ActivateTicketSystem;
    }

    public function setActivateTicketSystem(bool $ActivateTicketSystem): self
    {
        $this->ActivateTicketSystem = $ActivateTicketSystem;

        return $this;
    }

    public function isShowGraphDcCurrGrp(): bool
    {
        return $this->showGraphDcCurrGrp;
    }

    public function setShowGraphDcCurrGrp(bool $showGraphDcCurrGrp): void
    {
        $this->showGraphDcCurrGrp = $showGraphDcCurrGrp;
    }

    public function isGridTicket(): ?bool
    {
        return $this->gridTicket;
    }

    public function setGridTicket(?bool $gridTicket): self
    {
        $this->gridTicket = $gridTicket;

        return $this;
    }
    public function getDCCableLosses(): ?string
    {
        return $this->DCCableLosses;
    }

    public function setDCCableLosses(?string $DCCableLosses): self
    {
        $this->DCCableLosses = $DCCableLosses;

        return $this;
    }

    public function getMissmatchingLosses(): ?string
    {
        return $this->MissmatchingLosses;
    }

    public function setMissmatchingLosses(?string $MissmatchingLosses): self
    {
        $this->MissmatchingLosses = $MissmatchingLosses;

        return $this;
    }

    public function getInverterEfficiencyLosses(): ?string
    {
        return $this->InverterEfficiencyLosses;
    }

    public function setInverterEfficiencyLosses(string $InverterEfficiencyLosses): self
    {
        $this->InverterEfficiencyLosses = $InverterEfficiencyLosses;

        return $this;
    }

    public function getShadingLosses(): ?string
    {
        return $this->ShadingLosses;
    }

    public function setShadingLosses(?string $ShadingLosses): self
    {
        $this->ShadingLosses = $ShadingLosses;

        return $this;
    }

    public function getACCableLosses(): ?string
    {
        return $this->ACCableLosses;
    }

    public function setACCableLosses(?string $ACCableLosses): self
    {
        $this->ACCableLosses = $ACCableLosses;

        return $this;
    }

    public function getTransformerLosses(): ?string
    {
        return $this->TransformerLosses;
    }

    public function setTransformerLosses(?string $TransformerLosses): self
    {
        $this->TransformerLosses = $TransformerLosses;

        return $this;
    }

    public function getTransformerLimitation(): ?string
    {
        return $this->transformerLimitation;
    }

    public function setTransformerLimitation(?string $transformerLimitation): self
    {
        $this->transformerLimitation = $transformerLimitation;

        return $this;
    }

    public function getInverterLimitation(): ?string
    {
        return $this->inverterLimitation;
    }

    public function setInverterLimitation(?string $inverterLimitation): self
    {
        $this->inverterLimitation = $inverterLimitation;

        return $this;
    }

    public function getDynamicLimitations(): ?string
    {
        return $this->dynamicLimitations;
    }

    public function setDynamicLimitations(?string $dynamicLimitations): self
    {
        $this->dynamicLimitations = $dynamicLimitations;

        return $this;
    }

    public function getTotalKpi(): float
    {
        return (float)$this->inverterLimitation + (float)$this->transformerLimitation + (float)$this->dynamicLimitations + (float)$this->DCCableLosses + (float)$this->MissmatchingLosses + (float)$this->InverterEfficiencyLosses + (float)$this->ShadingLosses + (float)$this->ACCableLosses + (float)$this->TransformerLosses;
    }

    public function getKpiTicket(): ?bool
    {
        return $this->kpiTicket;
    }

    public function setKpiTicket(?bool $kpiTicket): void
    {
        $this->kpiTicket = $kpiTicket;
    }

    public function getPowerThreshold(): ?string
    {
        return $this->PowerThreshold ?? '0';
    }

    public function setPowerThreshold(?string $PowerThreshold): static
    {
        $this->PowerThreshold = $PowerThreshold;

        return $this;
    }

    public function getSensors(): Collection
    {
        return $this->sensors;
    }

    public function getSensorsInUse(): Collection
    {
        $criteria = AnlagenRepository::sensorsInUse();

        return $this->sensors->matching($criteria);
    }

    public function addSensor(AnlageSensors $sensor): static
    {
        if (!$this->sensors->contains($sensor)) {
            $this->sensors->add($sensor);
            $sensor->setAnlage($this);
        }

        return $this;
    }

    public function removeSensor(AnlageSensors $sensor): static
    {
        if ($this->sensors->removeElement($sensor)) {
            // set the owning side to null (unless already changed)
            if ($sensor->getAnlage() === $this) {
                $sensor->setAnlage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AnlagePpcs>
     */
    public function getPpcs(): Collection
    {
        return $this->ppcs;
    }

    /**
     * @return Collection<int, AnlagePpcs>
     */
    public function getPpcsInUse(): Collection
    {
        $criteria = AnlagenRepository::ppcsInUse();

        return $this->ppcs->matching($criteria);
    }

    public function addPpc(AnlagePpcs $ppc): static
    {
        if (!$this->ppcs->contains($ppc)) {
            $this->ppcs->add($ppc);
            $ppc->setAnlage($this);
        }

        return $this;
    }

    public function removePpc(AnlagePpcs $ppc): static
    {
        if ($this->ppcs->removeElement($ppc)) {
            // set the owning side to null (unless already changed)
            if ($ppc->getAnlage() === $this) {
                $ppc->setAnlage(null);
            }
        }

        return $this;
    }

    /**
     * New Algorithme for TicketGeneration
     */
    public function isNewAlgorythm(): ?bool
    {
        return $this->newAlgorythm;
    }

    public function setNewAlgorythm(?bool $newAlgorythm): void
    {
        $this->newAlgorythm = $newAlgorythm;
    }

    public function getMinIrrThreshold(): mixed
    {
        return min($this->getThreshold1PA0(), $this->getThreshold1PA1(), $this->getThreshold1PA2(), $this->getThreshold1PA3());
    }

    public function getPrformular0Image(): string
    {
        $name = str_replace(':', '_', $this->prFormular0);
        $name = str_replace('/', '_', $name);
        return '/images/formulas/' . $name . '.png';
    }

    public function getPrFormular1Image(): string
    {
        $name = str_replace(':', '_', $this->prFormular1);
        $name = str_replace('/', '_', $name);
        return '/images/formulas/' . $name . '.png';
    }

    public function getPrFormular2Image(): string
    {
        $name = str_replace(':', '_', $this->prFormular2);
        $name = str_replace('/', '_', $name);
        return '/images/formulas/' . $name . '.png';
    }

    public function getPrFormular3Image(): string
    {
        $name = str_replace(':', '_', $this->prFormular3);
        $name = str_replace('/', '_', $name);
        return '/images/formulas/' . $name . '.png';
    }

    public function isPpcBlockTicket(): ?bool
    {
        return $this->ppcBlockTicket;
    }

    public function setPpcBlockTicket(?bool $ppcBlockTicket): static
    {
        $this->ppcBlockTicket = $ppcBlockTicket;

        return $this;
    }

    public function getInternalTicketSystem(): ?bool
    {
        return $this->internalTicketSystem;
    }

    public function setInternalTicketSystem(?bool $internalTicketSystem): void
    {
        $this->internalTicketSystem = $internalTicketSystem;
    }

    /**
     * @return Collection<int, AnlageStringAssignment>
     */
    public function getAnlageStringAssignments(): Collection
    {
        return $this->anlageStringAssignments;
    }

    public function addAnlageStringAssignment(AnlageStringAssignment $anlageStringAssignment): static
    {
        if (!$this->anlageStringAssignments->contains($anlageStringAssignment)) {
            $this->anlageStringAssignments->add($anlageStringAssignment);
            $anlageStringAssignment->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageStringAssignment(AnlageStringAssignment $anlageStringAssignment): static
    {
        if ($this->anlageStringAssignments->removeElement($anlageStringAssignment)) {
            // set the owning side to null (unless already changed)
            if ($anlageStringAssignment->getAnlage() === $this) {
                $anlageStringAssignment->setAnlage(null);
            }
        }

        return $this;
    }

    public function getLastAnlageStringAssigmentUpload(): ?\DateTimeInterface
    {
        return $this->lastAnlageStringAssigmentUpload;
    }

    public function setLastAnlageStringAssigmentUpload(?\DateTimeInterface $lastAnlageStringAssigmentUpload): static
    {
        $this->lastAnlageStringAssigmentUpload = $lastAnlageStringAssigmentUpload;

        return $this;
    }

}

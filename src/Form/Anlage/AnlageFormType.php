<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\User;
use App\Entity\WeatherStation;
use App\Form\Type\SwitchType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;

use App\Repository\UserRepository;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class AnlageFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,

    ){
    }

    private function getUserChoices(): array
    {
        $adminUsers = $this->userRepository->findByRole('ROLE_ALERT_RECEIVER');
        $choices = [];

        foreach ($adminUsers as $user) {
            $choices[$user->getname()] = $user->getEmail();
        }

        return $choices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $isG4NUser = $this->security->isGranted('ROLE_G4N');

        $anlage = $builder->getData();
        if (!$anlage instanceof Anlage) {
            throw new \RuntimeException('Invalid entity.');
        }

        $_SESSION['tempOwnerId'] = $anlage->getEigner()->getEignerId();

        $prArray = self::prFormulars();

        $pldAlgorithmArray = [
            'Lelystad' => 'Lelystad',
            'Leek/Kampen' => 'Leek/Kampen',
        ];
        $epcReportArry = [
            'Kein Bericht' => 'no',
            'PR Garantie' => 'prGuarantee',
            'Ertrags Garantie' => 'yieldGuarantee',
        ];
        $pldDiviorArray = [
            'Expected Energy' => 'expected',
            'Guaranteed Expected Energy' => 'guaranteedExpected',
        ];

        $paFormulars = self::paFormulars();


        $tooltipTextPlantType = "
                                    <ul>
                                        <li>1: Fall 'Andjik, …' <br>
                                            AC Gruppen = Trafostationen o.ä.<br>
                                            DC Gruppen = Inverter
                                        </li>
                                        <li>2: Fall 'Lelystad 1 & 2, …' <br>
                                            AC Gruppen = DC Gruppen – beides Inverter
                                        </li>
                                        <li>3: Fall 'Groningen, …' <br>
                                            AC Gruppen = Inverter<br>
                                            DC Gruppen = SCB Gruppen<br>
                                            (separate Tabelle für DC IST)
                                        </li>
                                        <li>4: Fall 'Guben, Forst, Subzin …' <br>
                                            AC Gruppen = Inverter<br>
                                            DC Gruppen = SCBs<br>
                                            (separate Tabelle für DC IST)
                                        </li>
                                    </ul>";

        $builder
            // ###############################################
            // ###                General                 ####
            // ###############################################

            // ##### Plant Location #######
            ->add('eigner', EntityType::class, [
                'label' => 'Owner of the Plant',
                'help' => '[eigner]',
                'class' => Eigner::class,
                'choice_label' => 'firma',
                'required' => true,
                'disabled' => !($isDeveloper || $isAdmin),
            ])
            ->add('anlName', TextType::class, [
                'label' => 'Plant Name',
                'help' =>  '[anlName]<br>The Name of the Plant',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('projektNr', TextType::class, [
                'label' => 'Project Nummer',
                'help' => '[projektNr]<br>optional Project No',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlStrasse', TextType::class, [
                'label' => 'Street',
                'help' => '[anlStrasse]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlPlz', TextType::class, [
                'label' => 'ZIP Code',
                'help' => '[anlPlz]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlOrt', TextType::class, [
                'label' => 'City',
                'help' => '[anlOrt]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('country', TextType::class, [
                'label' => 'Shortcut for the country (de, nl, ...)',
                'help' => '[country]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlGeoLat', TextType::class, [
                'label' => 'Geografische Breite (Latitude) [Dezimalgrad]',
                'help' => '[anlGeoLat]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlGeoLon', TextType::class, [
                'label' => 'Geografische Länge (Longitude) [Dezimalgrad]',
                'help' => '[anlGeoLon]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('customPlantId', TextType::class, [
                'label' => 'Identifier/s to select Plant via API (e.g. VCOM can be more then one seperatet with ,)',
                'help' => '[customPlantId/s you can add one or more VCOM-Ids like ABC2X,CDE3F]',
                'empty_data' => '',
                'required' => false,
                'disabled' => !$isG4NUser,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notizen zur Anlage',
                'help' => '[notes]',
                'attr' => ['rows' => '6'],
                'empty_data' => '',
                'required' => false,
            ])

            // ##### Plant Base Configuration #######
            ->add('anlIntnr', TextType::class, [
                'label' => 'Datenbankkennung',
                'help' => '[anlIntnr]',
                'empty_data' => '',
                'required' => true,
                'disabled' => !$isDeveloper,
            ])
            ->add('anlType', ChoiceType::class, [
                'label' => 'Anlagen Typ',
                'help' => '[anlType]',
                'choices' => ['String WR' => 'string', 'ZWR' => 'zwr', 'Master Slave' => 'masterslave'],
                'placeholder' => 'Please Choose',
                'empty_data' => '',
                'required' => true,
                'disabled' => !$isG4NUser,
            ])
            ->add('anlBetrieb', null, [
                'label' => 'In Betrieb seit:',
                'help' => "[anlBetrieb]<br>Wird für die Berechnung der Degradation benötigt<br> In Betrieb seit " . $anlage->getBetriebsJahre() . " Jahr(en).",
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('anlZeitzone', ChoiceType::class, [
                'label' => 'Zeit Korrektur Anlage',
                'help' => '[anlZeitzone]',
                'choices' => self::timeArray(),
                'placeholder' => 'Please Choose',
                'empty_data' => '+0',
                'disabled' => !$isG4NUser,
            ])
            ->add('anlInputDaily', ChoiceType::class, [
                'label' => 'Nur einmal am Tag neue Daten',
                'help' => '[anlInputDaily]',
                'choices' => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'No',
                'disabled' => !($isDeveloper),
            ])
            ->add('configType', ChoiceType::class, [
                'label' => 'Configuration der Anlage',
                'help' => '[configType]<br>' . $tooltipTextPlantType,
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'placeholder' => 'Please Choose',
                'empty_data' => 1,
                'disabled' => !($isDeveloper || $isAdmin),
            ])
            ->add('pathToImportScript', TextType::class, [
                'label' => 'Path to Import Script',
                'help' => '[pathToImportScript]',
                'empty_data' => '',
                'disabled' => !$isG4NUser,
            ]);

        $builder
            // ##### WeatherStation #######
            ->add('WeatherStation', EntityType::class, [
                'label' => 'Wetterstation',
                'help' => '[WeatherStation]',
                'class' => WeatherStation::class,
                'choice_label' => fn(WeatherStation $station) => sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation()),
                'required' => true,
                'disabled' => !$isDeveloper,
            ])
            ->add('useLowerIrrForExpected', SwitchType::class, [
                'label' => 'Benutze \'IrrLower\' für die Berechnung Expected',
                'help' => '[useLowerIrrForExpected]',
            ])

            // ###############################################
            // ###       Plant Data / Configuration       ####
            // ###############################################
            ->add('pnom', TextType::class, [
                'label' => 'Anlagenleistung (für PA Berechnung) <br>[kWp]',
                'help' => '[pNom]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
                'attr' => ['pattern' => '[0-9]{7}', 'maxlength' => 12]
            ])
            ->add('kwPeakPvSyst', TextType::class, [
                'label' => 'Anlagenleistung PVSYST <br> [kWp]',
                'help' => '[kwPeakPvSyst]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
                'attr' => ['pattern' => '[0-9]{7}', 'maxlength' => 12]
            ])
            ->add('kwPeakPLDCalculation', TextType::class, [
                'label' => 'Anlagenleistung für PLD Berechnung <br> [kWp]',
                'help' => '[kwPeakPLDCalculation]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
                'attr' => ['pattern' => '[0-9]{7}', 'maxlength' => 12]
            ])
            ->add('tempCorrCellTypeAvg', TextType::class, [
                'label' => 't Cell AVG ',
                'help' => '[tempCorrCellTypeAvg]<br>(nur für PR Algor. Lelystadt, wenn 0 dann ohne Temperatur korrektur)',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('tempCorrGamma', TextType::class, [
                'label' => 'Gamma',
                'help' => '[tempCorrGamma]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '-0.4',
                'disabled' => !$isDeveloper,
            ])
            ->add('tempCorrA', TextType::class, [
                'label' => 'A',
                'help' => '[tempCorrA]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '-3.56',
                'disabled' => !$isDeveloper,
            ])
            ->add('tempCorrB', TextType::class, [
                'label' => 'B',
                'help' => '[tempCorrB]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '-0.0750',
                'disabled' => !$isDeveloper,
            ])
            ->add('tempCorrDeltaTCnd', TextType::class, [
                'label' => 'Delta T CND',
                'help' => '[tempCorrDeltaTCnd]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '3.0',
                'disabled' => !$isDeveloper,
            ])
            ->add('degradationPR', TextType::class, [
                'label' => 'Degradation for PR Calc',
                'help' => '[degradationPR]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0.5',
            ])
            ->add('useGridMeterDayData', SwitchType::class, [
                'label' => 'Use externe GridMeter Data',
                'help' => 'Use externe GridMeter Data [Yes / No]',
            ])
            ->add('contractualAvailability', TextType::class, [
                'label' => 'Verfügbarkeit in %',
                'help' => '[contractualAvailability]',
                'label_html' => true,
                'empty_data' => '0',
            ])
            ->add('contractualPR', TextType::class, [
                'label' => 'PR (Garantie) in %',
                'help' => '[contractualPR]',
                'label_html' => true,
                'empty_data' => '0',
            ])
            ->add('contractualPower', TextType::class, [
                'label' => 'Jahres Leistung in [kWh]',
                'help' => '[contractualPower]',
                'label_html' => true,
                'empty_data' => '',
            ])
            ->add('designPR', TextType::class, [
                'label' => 'PR Design (pvSyst) [%]',
                'help' => '[designPR]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
            ])
            ->add('isOstWestAnlage', ChoiceType::class, [
                'label' => 'Plant is oriented east/west',
                'help' => '[isOstWestAnlage]',
                'label_html' => true,
                'choices' => ['No' => '0', 'Yes' => '1'],
                // 'placeholder'   => 'Please Choose',
                'empty_data' => 'No',
                'attr' => ['style' => 'width: 95px']
            ])
            ->add('powerEast', TextType::class, [
                'label' => 'Pnom Osten [kWp] ',
                'help' => '[powerEast]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
                'attr' => ['pattern' => '[0-9]{7}', 'maxlength' => 7, 'style' => 'width: 95px']
            ])
            ->add('powerWest', TextType::class, [
                'label' => 'Pnom Westen [kWp] ',
                'help' => '[powerWest]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
                'attr' => ['pattern' => '[0-9]{7}', 'maxlength' => 7, 'style' => 'width: 95px']
            ])
            ->add('pacDate', null, [
                'label' => 'PAC Date',
                'help' => '[pacDate]',
                'label_html' => true,
                'widget' => 'single_text',
                'input' => 'datetime',
                // 'empty_data'    => new \DateTime('now'),
            ])
            ->add('pacDateEnd', null, [
                'label' => 'PAC Zeitraum Ende',
                'help' => '[pacDate]',
                'label_html' => true,
                'widget' => 'single_text',
                'input' => 'datetime',
                // 'empty_data'    => new \DateTime('now'),
            ])
            ->add('usePac', SwitchType::class, [
                'label' => 'Use PAC Date',
                'help' => '[usePac]',
            ])
            ->add('pacDuration', TextType::class, [
                'label' => 'PAC Zeitraums in Monaten',
                'help' => '[pacDuration]',
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('facDateStart', null, [
                'label' => 'FAC start date',
                'help' => '[facDateStart]',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('facDate', null, [
                'label' => 'FAC end date',
                'help' => '[facDate]',
                'label_html' => true,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('epcReportStart', null, [
                'label' => 'EPC report start date',
                'help' => '[epcReportStart]',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('epcReportEnd', null, [
                'label' => 'EPC report end date',
                'help' => '[epcReportEnd]',
                'label_html' => true,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('lid', TextType::class, [
                'label' => 'Verlust Risikoabsch. (LID) [%]',
                'help' => '[lid]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('annualDegradation', TextType::class, [
                'label' => 'Verlust Annual Degrad. [%]',
                'help' => '[annualDegradation]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('pldPR', TextType::class, [
                'label' => 'PLD PR (VE) [EUR/kWh]',
                'help' => '[pldPR]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('pldNPValue', TextType::class, [
                'label' => 'PLD NP Wert [%]',
                'help' => '[pldNPValue]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('pldYield', TextType::class, [
                'label' => 'PLD Ertrag <br>[faktor]',
                'help' => '[pldYield]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('pldDivisor', ChoiceType::class, [
                'label' => 'PLD Divisor (Welcher Wert soll als Divisior in PLD Formel genutzt werden)',
                'help' => '[pldDivisor]',
                'label_html' => true,
                'choices' => $pldDiviorArray,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('usePnomForPld', SwitchType::class, [
                'label' => 'use Pnom for PLD <br>calculation',
                'help' => '[usePnomForPld]',
                'label_html' => true,
                'required' => false,
            ])
            ->add('transformerTee', TextType::class, [
                'label' => 'Abschlag Trafoverlust [%]',
                'help' => '[transformerTee]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('guaranteeTee', TextType::class, [
                'label' => 'Abschlag Garantie [%]',
                'help' => '[guaranteeTee]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('hasDc', SwitchType::class, [
                'label' => 'Anlage hat DC Daten',
                'help' => '[hasDc]',
            ])
            ->add('pldAlgorithm', ChoiceType::class, [
                'label' => 'Select the PLD Calculation Algorrithm',
                'choices' => $pldAlgorithmArray,
                'help' => '[pldAlgorithm]',
            ])
            ->add('hasStrings', SwitchType::class, [
                'label' => 'Plant has String Data',
                'help' => '[hasStrings]<br>Yes / No',
            ])
            ->add('hasPPC', SwitchType::class, [
                'label' => 'Plant has PPC',
                'help' => '[Yes / No]',
            ])
            ->add('usePPC', SwitchType::class, [
                'label' => 'Respect PPC Signal on calc',
                'help' => '[usePPC]<br>Power, TheoPower, Irradiation will be excluded if PPC signal is lower 100 (Yes / No)',
            ])
            ->add('ignoreNegativEvu', SwitchType::class, [
                'label' => 'Ignore negative EVU values',
                'help' => '[ignoreNegativEvu]<br>(Yes / No)',
            ])
            ->add('hasPannelTemp', SwitchType::class, [
                'label' => 'Plant has Pannel Temperatur',
                'help' => '[hasPannelTemp]<br>(Yes / No)',
            ])
            // ###############################################
            // ###          FORECAST                      ####
            // ###############################################
            ->add('useDayForecast', SwitchType::class, [
                'label' => 'Use forecast by day for this plant',
                'help' => '[On / Off]',
                'required' => false,
            ])
            ->add('useDayaheadForecast', SwitchType::class, [
                'label' => 'Use Dayahead forecast for this plant',
                'help' => '[On / Off]',
                'required' => false,
            ])
            ->add('degradationForecast', TextType::class, [
                'label' => 'Degradation, only forecast [%]',
                'help' => '[Degradation forecast in %]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
                'attr' => ['pattern' => '[0-9]{3}', 'maxlength' => 3, 'style' => 'width: 55px']
            ])
            ->add('lossesForecast', TextType::class, [
                'label' => 'Losses, only forecast [%]',
                'help' => '[Losses forecast in %]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
                'attr' => ['pattern' => '[0-9]{3}', 'maxlength' => 3, 'style' => 'width: 55px']
            ])
            ->add('bezMeridan', TextType::class, [
                'label' => 'Reference meridian',
                'help' => '[Reference meridian for mitteleuropa are 15]',
                'label_html' => true,
                'required' => true,
                'empty_data' => '0',
                'attr' => ['pattern' => '[0-9]{2}', 'maxlength' => 2, 'style' => 'width: 55px']
            ])
            ->add('modNeigung', TextType::class, [
                'label' => 'Module alignment',
                'help' => '[Module alignment in degrees , example 30]',
                'label_html' => true,
                'empty_data' => '0',
                'attr' => ['pattern' => '[0-9]{2}', 'maxlength' => 2, 'style' => 'width: 55px']
            ])
            ->add('modAzimut', TextType::class, [
                'label' => 'Modul azimut',
                'help' => '[Modul azimut in degrees for S=180 O=90 W=270 ]',
                'label_html' => true,
                'empty_data' => '0',
                'attr' => ['pattern' => '[0-9]{3}', 'maxlength' => 3, 'style' => 'width: 55px']
            ])
            ->add('albeto', TextType::class, [
                'label' => 'Albedo',
                'help' => '[The albedo are 0.15 for grass or 0.3 for roof]',
                'label_html' => true,
                'empty_data' => '0',
                'attr' => ['maxlength' => 4, 'style' => 'width: 55px']
            ])
            ->add('datFilename', FileType::class, [
                'label' => 'Upload the metonorm *dat file',
                'mapped' => false,
                'help' => '[The generated meteonorm *dat file]',
                'attr' => ['class' => 'filestyle'],
                'constraints' => [
                    new File([
                        'maxSize' => '5120k',
                        'mimeTypes' => [],
                        'mimeTypesMessage' => 'Please upload a valid *dat file',
                    ])
                ],
                'required' => true,
            ])
            ->add('dataSourceAM', TextareaType::class, [
                'label' => 'Summary DataSources AM Report',
                'empty_data' => 'Module Inclination: <br>Module Name: <br>Module Type: <br>Module Performance: <br>Number of Modules: <br>Inverter Name: <br>Inverter Type: <br>Number of Inverters:',
                #'config' => ['toolbar' => 'my_toolbar'],
            ])
            ->add('retrieveAllData', SwitchType::class, [
                'label' => 'Use all Data from begining of Working Time',
                'help' => '[retrieveAllData]',
            ])
            ->add('hasFrequency', SwitchType::class, [
                'label' => 'Has Frequency',
                'help' => '[hasFrequency]',
            ])
            // ###############################################
            // ###         HAS SUNSHADING MODEL           ####
            // ###############################################
            ->add('hasSunshadingModel', SwitchType::class, [
                'label' => 'Use the Sunshading Model for this plant',
                'help' => '[On / Off]',
                'required' => false,
            ])
            // ###############################################
            // ###         Is Tracker Eow           ####
            // ###############################################
            ->add('isTrackerEow', SwitchType::class, [
                'label' => 'Is a one axis tracker',
                'help' => 'Is a one axis tracker with east west orientation [Yes / No] <bR> Check that Plant is oriented east/west of [NO]',
                'required' => false,
            ])
            // ###############################################
            // ###            Availability                ####
            // ###############################################

            ->add('threshold1PA0', TextType::class, [
                'label' => 'lower threshold [W/qm] ',
                'help' => '[threshold1PA0]<br>(increase ti, if irraddiation is >= threshold 1 and <= threshold 2; increase ti_theo, if Irradiation >=  threshold 1)',
                'label_html' => true,
            ])
            ->add('threshold2PA0', TextType::class, [
                'label' => 'upper threshold (min Irr.) [W/qm] ',
                'help' => '[threshold2PA0]<br>(increas ti, if irradiation > threshold2 and power > 0)',
                'label_html' => true,
            ])
            ->add('threshold1PA1', TextType::class, [
                'label' => 'lower threshold [W/qm] ',
                'help' => '[threshold1PA1]<br>(increase ti, if irraddiation is >= threshold 1 and <= threshold 2; increase ti_theo, if Irradiation >=  threshold 1)',
                'label_html' => true,
            ])
            ->add('threshold2PA1', TextType::class, [
                'label' => 'upper threshold (min Irr.) [W/qm] ',
                'help' => '[threshold2PA1]<br>(increas ti, if irradiation > threshold2 and power > 0)',
                'label_html' => true,
            ])
            ->add('threshold1PA2', TextType::class, [
                'label' => 'lower threshold [W/qm] ',
                'help' => '[threshold1PA2]<br>(increase ti, if irraddiation is >= threshold 1 and <= threshold 2; increase ti_theo, if Irradiation >=  threshold 1)',
                'label_html' => true,
            ])
            ->add('threshold2PA2', TextType::class, [
                'label' => 'upper threshold (min Irr.) [W/qm] ',
                'help' => '[threshold2PA2]<br>(increas ti, if irradiation > threshold2 and power > 0)',
                'label_html' => true,
            ])
            ->add('threshold1PA3', TextType::class, [
                'label' => 'lower threshold [W/qm] ',
                'help' => '[threshold1PA3]<br>(increase ti, if irraddiation is >= threshold 1 and <= threshold 2; increase ti_theo, if Irradiation >=  threshold 1)',
                'label_html' => true,
            ])
            ->add('threshold2PA3', TextType::class, [
                'label' => 'upper threshold (min Irr.) [W/qm] ',
                'help' => '[threshold2PA3]<br>(increas ti, if irradiation > threshold2 and power > 0)',
                'label_html' => true,
            ])
            ->add('usePAFlag0', SwitchType::class, [
                'label' => 'Use PA Flag from Sensors',
                'help' => '[usePAFlag0]<br>Use special formular to calulate irr limit for PA',
            ])
            ->add('usePAFlag1', SwitchType::class, [
                'label' => 'Use PA Flag from Sensors',
                'help' => '[usePAFlag0]<br>Use special formular to calulate irr limit for PA',
            ])
            ->add('usePAFlag2', SwitchType::class, [
                'label' => 'Use PA Flag from Sensors',
                'help' => '[usePAFlag0]<br>Use special formular to calulate irr limit for PA',
            ])
            ->add('usePAFlag3', SwitchType::class, [
                'label' => 'Use PA Flag from Sensors',
                'help' => '[usePAFlag0]<br>Use special formular to calulate irr limit for PA',
            ])
            ->add('paFormular0', ChoiceType::class, [
                'label' => 'PA Formular',
                'help' => '[paFormular0]',
                'label_html' => true,
                'choices' => $paFormulars,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('paFormular1', ChoiceType::class, [
                'label' => 'PA Formular',
                'help' => '[paFormular1]',
                'label_html' => true,
                'choices' => $paFormulars,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('paFormular2', ChoiceType::class, [
                'label' => 'PA Formular',
                'help' => '[paFormular2]',
                'label_html' => true,
                'choices' => $paFormulars,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('paFormular3', ChoiceType::class, [
                'label' => 'PA Formular',
                'help' => '[paFormular3]',
                'label_html' => true,
                'choices' => $paFormulars,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('treatingDataGapsAsOutage', SwitchType::class, [
                'label' => 'Treat Data Gaps as Outage',
                'help' => '[treatingDataGapsAsOutage]',
            ])
            ->add('prFormular0', ChoiceType::class, [
                'label' => 'PR Formular',
                'help' => '[paFormular0]',
                'label_html' => true,
                'choices' => $prArray,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('prFormular1', ChoiceType::class, [
                'label' => 'PR Formular',
                'help' => '[paFormular1]',
                'label_html' => true,
                'choices' => $prArray,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('prFormular2', ChoiceType::class, [
                'label' => 'PR Formular',
                'help' => '[prFormular2]',
                'label_html' => true,
                'choices' => $prArray,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('prFormular3', ChoiceType::class, [
                'label' => 'PR Formular',
                'help' => '[prFormular3]',
                'label_html' => true,
                'choices' => $prArray,
                'empty_data' => 'expected',
                'expanded' => false,
                'multiple' => false,
            ])

            // ###############################################
            // ###            Ticket & Alert              ####
            // ###############################################

            ->add('ActivateTicketSystem', SwitchType::class, [
                'label' => 'Activate ticket autogeneration',
                'help' => '[ActivateTicketSystem] ',
                'attr' => ['data-plant-target' => 'activateTicket', 'data-action' => 'plant#activateTicket'],
                'disabled' => !$isG4NUser,
            ])
        ;
        if ($isG4NUser) {
            $builder
                ->add('internalTicketSystem', SwitchType::class, [
                    'label' => 'Activate internal ticket autogeneration',
                    'help' => '<br>[internalTicketSystem]',
                ]);
        }
        $builder
            ->add('newAlgorythm', SwitchType::class, [
                'label' => 'Use the new Algorithm',
                'help' => 'The new algorithm prioritizes joining tickets that begin at the same time, and the old one joins tickets if the begin and end match<br>[newAlgorythm]',
                'attr' => ['data-plant-target' => 'ticket'],
                'disabled' => !$isG4NUser,
            ])
            ->add('freqBase', TextType::class, [
                'label' => 'Base frequency of the Plant [Hz]',
                'help' => '[freqBase]',
                'attr' => ['data-plant-target' => 'ticket'],
                'empty_data' => '50',
            ])
            ->add('freqTolerance', TextType::class, [
                'label' => 'Frequency tolerance of the Plant [Hz]',
                'help' => '[hasFrequency]',
                'attr' => ['data-plant-target' => 'ticket'],
                'empty_data' => '2',
            ])
            ->add('expectedTicket', SwitchType::class, [
                'label' => 'Activate Expected Tickets',
                'help' => '[ExpectedTicket]',
                'attr' => ['data-plant-target' => 'ticket'],
                'disabled' => !$isG4NUser,
            ])
            ->add('percentageDiff',TextType::class, [
                'label' => 'Ticket Expected limit [%]',
                'help' => '[percentageDiff]',
                'attr' => ['data-plant-target' => 'ticket'],
                'empty_data' => '',
                'disabled' => !$isG4NUser,
            ])
            ->add('weatherTicket', SwitchType::class, [
                'label' => 'Activate Weather Ticket',
                'help' => '[WeatherTicket]',
                'attr' => ['data-plant-target' => 'ticket'],
                'disabled' => !$isG4NUser,
            ])
            ->add('kpiTicket', SwitchType::class, [
                'label' => 'Activate Performace (KPI) Tickets',
                'help' => '[kpiTicket]',
                'disabled' => !$isG4NUser,
            ])
            ->add('gridTicket', SwitchType::class, [
                'label' => 'Activate Grid Ticket',
                'help' => '[Grid Ticket]',
                'attr' => ['data-plant-target' => 'ticket'],
                'disabled' => !$isG4NUser,
            ])
            ->add('PowerThreshold', TextType::class, [
                'label' => 'Ticket Power minimum value [kW]',
                'help' => "Minimum Power to set a Inverter to 'working', is also used for PA calculation.<br>[PowerThreshold]",
                'attr' => ['data-plant-target' => 'ticket'],
                'empty_data' => '0',
            ])
            ->add('ppcBlockTicket', SwitchType::class, [
                'label' => 'PPC blocks the generation of inverter tickets',
                'help' => '[ppcBlockTicket]',
                'attr' => ['data-plant-target' => 'ticket'],
                'empty_data' => 'false',
                'disabled' => !$isG4NUser,
            ])

            ->add('allowSendAlertMail', SwitchType::class, [
                'label' => 'Activate email alert ',
                'help' => '[allowSendAlertMail]',
                'required' => false,
                'disabled' => !$isG4NUser,
            ])

            ->add('alertMailReceiver', ChoiceType::class, [
                'help' => '[alertMailReceiver]',
                'choices' => $this->getUserChoices(),
                'multiple' => true,
                'expanded' => true,
                'label' => 'Send an email to',
                'required' => false,
                'disabled' => !$isG4NUser,
            ])

            ->add('alertCheckInterval', IntegerType::class, [
                'label' => 'Send a reminder email after (minutes)',
                'help' => '[alertCheckInterval]',
                'empty_data' => 120,
                'required' => false,
                'disabled' => !$isG4NUser,
            ])
            // ###############################################
            // ###               Reports                  ####
            // ###############################################

            ->add('epcReportType', ChoiceType::class, [
                'label' => 'Welchen EPC Report',
                'help' => '[epcReportType]',
                'choices' => $epcReportArry,
                // 'placeholder'   => 'Please Choose',
                'empty_data' => 'no',
                'expanded' => false,
                'multiple' => false,
            ])

            // ###############################################
            // ###            Settings NEW                ####
            // ###############################################

            ->add('settings', AnlageSettingsFormType::class, [

            ])

            // ###############################################
            // ###              Settings                  ####
            // ###############################################
            ->add('anlView', ChoiceType::class, [
                'label' => 'Anlage für Eigner:',
                'help' => '[anlView]',
                'choices' => ['aktiv' => 'Yes', 'deaktiviert' => 'No'],
                'attr' => ['style' => 'width: 6em;'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'No',
            ])
            ->add('anlHidePlant', ChoiceType::class, [
                'label' => 'Anlage komplett ausblenden (Eigner und Admins)',
                'help' => '[anlHidePlant]',
                'label_html' => true,
                'choices' => ['Yes' => 'Yes', 'No' => 'No'],
                'attr' => ['style' => 'width: 6em;'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'Yes',
            ])

            ->add('anlMute', ChoiceType::class, [
                'label' => 'Anlage stummschalten (keine Fehlermeldungen senden) (inaktiv)',
                'help' => '[anlMute]',
                'label_html' => true,
                'choices' => ['Yes' => 'Yes', 'No' => 'No'],
                'attr' => ['style' => 'width: 6em;'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'No',
            ])

            ->add('anlMuteUntil', DateType::class, [
                'label' => 'Anlage stumgeschaltet bis:',
                'help' => '[anlMuteUntil]',
                'label_html' => true,
                'disabled' => true,
                'attr' => ['style' => 'width: 8em;'],
                'widget' => 'single_text',
            ])

            ->add('useCosPhi', SwitchType::class, [
                'label' => 'Aktiviere cosPhi',
                'help' => '[Yes / No]',
            ])

            ->add('calcPR', SwitchType::class, [
                'label' => 'PR und andere Werte berechnen (wenn Anlage noch nicht final eingerichtet, bitte auf \'No\' stellen)',
                'help' => '[calcPR]',
            ])
            ->add('excludeFromExpCalc', SwitchType::class, [
                'label' => 'Exclude from expected Calculation',
                'help' => '[isExcludeFromExpCalc]',
            ])

            // ###############################################
            // ###         Configuartion Backend          ####
            // ###############################################
            ->add('showOnlyUpperIrr', SwitchType::class, [
                'label' => 'Show one Irr Line',
                'help' => '[showOnlyUpperIrr]',
                'attr' => ['switch_size' => 'tiny'],
            ])
            ->add('showStringCharts', SwitchType::class, [
                'help' => '[showStringCharts]',
            ])
            ->add('showAvailability', SwitchType::class, [
                'help' => '[showAvailability]',
            ])
            ->add('showAvailabilitySecond', SwitchType::class, [
                'help' => '[showAvailabilitySecond]',
            ])
            ->add('showInverterPerformance', SwitchType::class, [
                'help' => '[showInverterPerformance]',
            ])
            ->add('showEvuDiag', SwitchType::class, [
                'label' => 'EVU',
                'help' => '[showEvuDiag]',
                'attr' => ['switch_size' => 'tiny'],
            ])
            ->add('showInverterOutDiag', SwitchType::class, [
                'label' => 'Inverter',
                'help' => '[showInverterOutDiag]',
                'attr' => ['switch_size' => 'tiny'],
            ])
            ->add('showCosPhiDiag', SwitchType::class, [
                'label' => 'CosPhi',
                'help' => '[showCosPhiDiag]',
                'attr' => ['switch_size' => 'tiny'],
            ])
            ->add('showCosPhiPowerDiag', SwitchType::class, [
                'label' => 'Cos Phi Power',
                'help' => '[showCosPhiPowerDiag]',
                'attr' => ['switch_size' => 'tiny'],
            ])
            ->add('showPR', SwitchType::class, [
                'label' => 'Zeige Diagramm \'PR\'',
                'help' => '[showPR]',
            ])
            ->add('showPvSyst', SwitchType::class, [
                'label' => 'Zeige Tabelle \'PvSyst\'',
                'help' => '[showPvSyst]',
            ])
            ->add('showForecast', SwitchType::class, [
                'label' => 'Zeige Forecast',
                'help' => '[showForecast]',
            ])


            // ###############################################
            // ###              AM Report                 ####
            // ###############################################

            ->add('DCCableLosses', TextType::class,[
                'label' => 'DC Cable Losses[%]',
            ])
            ->add('MissmatchingLosses', TextType::class,[
                'label' => 'Missmatching Losses[%]',
            ])
            ->add('InverterEfficiencyLosses', TextType::class,[
                'label' => 'Inverter Efficiency Losses[%]',
            ])
            ->add('ShadingLosses', TextType::class,[
                'label' => 'Shading Losses[%]',
            ])
            ->add('ACCableLosses', TextType::class,[
                'label' => 'AC Cable Losses[%]',
            ])
            ->add('TransformerLosses', TextType::class,[
                'label' => 'Transformer Losses[%]',
            ])
            ->add('inverterLimitation', TextType::class,[
                'label' => 'Inverter Limitations[%]',
            ])
            ->add('transformerLimitation', TextType::class,[
                'label' => 'Transformer Limitations[%]',
            ])


            // ###############################################
            // ###              Relations                 ####
            // ###############################################
            ->add('modules', CollectionType::class, [
                'entry_type' => ModulesListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
            ->add('timesConfigs', CollectionType::class, [
                'entry_type' => TimeConfigListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
            ->add('anlageSunShading', CollectionType::class, [
                'entry_type' => SunShadingListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'mapped' => true,
            ])

            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################
            ->add('save', SubmitType::class, [
                'label' => 'Save Plant',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close Plant',
                'attr' => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close without save',
                'attr' => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
            ->add('savecreatedb', SubmitType::class, [
                'label' => 'Save and Create Databases',
                'attr' => ['class' => 'secondary small', 'formnovalidate' => 'formnovalidate'],
            ])
        ;

}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Anlage::class,
            'anlagenId' => '',
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}


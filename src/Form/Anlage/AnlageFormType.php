<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\WeatherStation;
use App\Form\Type\SwitchType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AnlageFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct(
        private Security $security
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin     = $this->security->isGranted('ROLE_ADMIN');

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
                                            DC Gruppen = SCB Gruppen
                                        </li>
                                        <li>4: Fall 'Guben, Forst, Subzin …' <br>
                                            AC Gruppen = Inverter<br>
                                            DC Gruppen = SCBs
                                        </li>
                                    </ul>";

        $builder
            // ###############################################
            // ###                General                 ####
            // ###############################################

            // ##### Plant Location #######
            ->add('eigner', EntityType::class, [
                'label' => 'Eigner',
                'help' => '[eigner]',
                'class' => Eigner::class,
                'choice_label' => 'firma',
                'required' => true,
                'disabled' => !($isDeveloper || $isAdmin),
            ])
            ->add('anlName', TextType::class, [
                'label' => 'Anlagen Name',
                'help' => '[anlName]',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('projektNr', TextType::class, [
                'label' => 'Projekt Nummer',
                'help' => '[projektNr]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlStrasse', TextType::class, [
                'label' => 'Strasse',
                'help' => '[anlStrasse]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlPlz', TextType::class, [
                'label' => 'PLZ',
                'help' => '[anlPlz]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlOrt', TextType::class, [
                'label' => 'Ort',
                'help' => '[anlOrt]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('country', TextType::class, [
                'label' => 'Land als Kürzel (de, nl, ...)',
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
                'label' => 'Identifier to select Plant via API (e.g. VCOM)',
                'help'  => '[customPlantId]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notizen zur Anlage',
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
            ])
            ->add('anlBetrieb', null, [
                'label' => 'In Betrieb seit:',
                'help' => '[anlBetrieb]',
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('anlZeitzone', ChoiceType::class, [
                'label' => 'Zeit Korrektur Anlage',
                'help' => '[anlZeitzone]',
                'choices' => self::timeArray(),
                'placeholder' => 'Please Choose',
                'empty_data' => '+0',
            ])
            ->add('anlInputDaily', ChoiceType::class, [
                'label' => 'Nur einmal am Tag neue Daten',
                'help' => '[anlInputDaily]',
                'choices' => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'No',
                'disabled' => !($isDeveloper),
            ])
            ->add('useNewDcSchema', ChoiceType::class, [
                'label' => 'Neues DC Database Schema',
                'help' => '[useNewDcSchema] <br> (separate Tabelle für DC IST)',
                'choices' => ['Yes' => '1', 'No' => '0'],
                'empty_data' => '0',
                'expanded' => false,
                'multiple' => false,
                'disabled' => !($isDeveloper || $isAdmin),
            ])
            ->add('configType', ChoiceType::class, [
                'label' => 'Configuration der Anlage',
                'help' => '[configType]<br>'.$tooltipTextPlantType,
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'placeholder' => 'Please Choose',
                'empty_data' => 1,
                'disabled' => !($isDeveloper || $isAdmin),
            ])
            ->add('pathToImportScript', TextType::class, [
                'label'     => 'Path to Import Script',
                'help'      => '[pathToImportScript]',
                'empty_data'    => '',
            ])
        ;


        $builder
            // ##### WeatherStation #######
            ->add('WeatherStation', EntityType::class, [
                'label' => 'Wetterstation',
                'help' => '[WeatherStation]',
                'class' => WeatherStation::class,
                'choice_label' => function (WeatherStation $station) {return sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation()); },
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
                'label' => 'Anlagenleistung [kWp] (für PA Berechnung)',
                'help' => '[pNom]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
            ])
            ->add('kwPeakPvSyst', TextType::class, [
                'label' => 'Anlagenleistung PVSYST [kWp]',
                'help' => '[kwPeakPvSyst]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
            ])

            ->add('kwPeakPLDCalculation', TextType::class, [
                'label' => 'Anlagenleistung für PLD Berechnung [kWp]',
                'help' => '[kwPeakPLDCalculation]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
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
                'label' => 'Nutze externe GridMeter Daten',
                'help' => '[useGridMeterDayData]',
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
            ])
            ->add('powerEast', TextType::class, [
                'label' => 'Pnom [kWp] Osten',
                'help' => '[powerEast]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
            ])
            ->add('powerWest', TextType::class, [
                'label' => 'Pnom [kWp] Westen',
                'help' => '[powerWest]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '',
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
                'label' => 'Anlage hat String Daten',
                'help' => '[hasStrings]',
            ])
            ->add('hasPPC', SwitchType::class, [
                'label' => 'Anlage hat Power Plant Controller Daten',
                'help' => '[hasPPC]',
            ])
            ->add('ignoreNegativEvu', SwitchType::class, [
                'label'     => 'Ignore negative EVU values on reporting',
                'help'      => '[ignoreNegativEvu]',
            ])
            ->add('hasPannelTemp', SwitchType::class, [
                'label' => 'Anlage hat Pannel Temperatur',
                'help' => '[hasPannelTemp]',
            ])
            ->add('useDayForecast', SwitchType::class, [
                'label' => 'use Forecast by Day',
                'help' => '[useDayForecast]',
                'required' => false,
            ])
            ->add('degradationForecast', TextType::class, [
                'label' => 'Degradation, only Forecast [%]',
                'help' => '[degradationForecast]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('lossesForecast', TextType::class, [
                'label' => 'Losses, only Forecast [%]',
                'help' => '[lossesForecast]',
                'label_html' => true,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('dataSourceAM', CKEditorType::class, [
                'label' => 'Explanation DataSources AM Report',
                'data' => 'Yield (Grid Meter): <br>Inverter out:',
                'config' => ['toolbar' => 'my_toolbar'],
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
            // ###            Availability                ####
            // ###############################################

            ->add('threshold1PA0', TextType::class, [
                'label' => 'lower threshold [W/qm] ',
                'help' => '[threshold1PA0] (ti,theo / Schwellwert 1)',
                'label_html' => true,
            ])
            ->add('threshold2PA0', TextType::class, [
                'label' => 'upper threshold (min Irr.) [W/qm] ',
                'help' => '[threshold2PA0] (ti / Schwellwert 2)',
                'label_html' => true,
            ])
            ->add('threshold1PA1', TextType::class, [
                'label' => 'lower threshold [W/qm] ',
                'help' => '[threshold1PA1] (ti,theo / Schwellwert 1)',
                'label_html' => true,
            ])
            ->add('threshold2PA1', TextType::class, [
                'label' => 'upper threshold (min Irr.) [W/qm] ',
                'help' => '[threshold2PA1] (ti / Schwellwert 2)',
                'label_html' => true,
            ])
            ->add('threshold1PA2', TextType::class, [
                'label' => 'lower threshold [WaW/qmtt] ',
                'help' => '[threshold1PA2] (ti,theo / Schwellwert 1)',
                'label_html' => true,
            ])
            ->add('threshold2PA2', TextType::class, [
                'label' => 'upper threshold (min Irr.) [W/qm] ',
                'help' => '[threshold2PA2] (ti / Schwellwert 2)',
                'label_html' => true,
            ])
            ->add('threshold1PA3', TextType::class, [
                'label' => 'lower threshold [W/qm] ',
                'help' => '[threshold1PA3] (ti,theo / Schwellwert 1)',
                'label_html' => true,
            ])
            ->add('threshold2PA3', TextType::class, [
                'label' => 'upper threshold (min Irr.) [W/qm] ',
                'help' => '[threshold2PA3] (ti / Schwellwert 2)',
                'label_html' => true,
            ])
            ->add('paFormular0', ChoiceType::class, [
                'label'         => 'PA Formular',
                'help'          => '[paFormular0]',
                'label_html'    => true,
                'choices'       => $paFormulars,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('paFormular1', ChoiceType::class, [
                'label'         => 'PA Formular',
                'help'          => '[paFormular1]',
                'label_html'    => true,
                'choices'       => $paFormulars,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('paFormular2', ChoiceType::class, [
                'label'         => 'PA Formular',
                'help'          => '[paFormular2]',
                'label_html'    => true,
                'choices'       => $paFormulars,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('paFormular3', ChoiceType::class, [
                'label'         => 'PA Formular',
                'help'          => '[paFormular3]',
                'label_html'    => true,
                'choices'       => $paFormulars,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('prFormular0', ChoiceType::class, [
                'label'         => 'PR Formular',
                'help'          => '[paFormular0]',
                'label_html'    => true,
                'choices'       => $prArray,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('prFormular1', ChoiceType::class, [
                'label'         => 'PR Formular',
                'help'          => '[paFormular1]',
                'label_html'    => true,
                'choices'       => $prArray,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('prFormular2', ChoiceType::class, [
                'label'         => 'PR Formular',
                'help'          => '[prFormular2]',
                'label_html'    => true,
                'choices'       => $prArray,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('prFormular3', ChoiceType::class, [
                'label'         => 'PR Formular',
                'help'          => '[prFormular3]',
                'label_html'    => true,
                'choices'       => $prArray,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])


            // ###############################################
            // ###            Ticket & Alert              ####
            // ###############################################

            ->add('ActivateTicketSystem', SwitchType::class, [
                'label' => 'Activate the Ticket System',
                'help' => '[ActivateTicketSystem]',
                'attr' => ['data-plant-target' => 'activateTicket', 'data-action'=>'plant#activateTicket'],
            ])
            ->add('newAlgorythm', SwitchType::class, [
                'label' => 'Use the new Algorithm ',
                'help' => 'The new algorithm prioritizes joining tickets that begin at the same time, and the old one joins tickets if the begin and end match',
                'attr' => ['data-plant-target' => 'ticket'],
            ])
            ->add('freqBase', TextType::class, [
                'label' => 'Base frequency of the Plant',
                'help' => '[freqBase]',
                'attr' => ['data-plant-target' => 'ticket'],
                'empty_data' => '',
            ])
            ->add('freqTolerance', TextType::class, [
                'label' => 'Frequency tolerance of the Plant',
                'help' => '[hasFrequency]',
                'attr' => ['data-plant-target' => 'ticket'],
                'empty_data' => '',
            ])
            ->add('expectedTicket', SwitchType::class, [
                'label' => 'Activate Expected Tickets',
                'help' => '[ExpectedTicket]',
                'attr' => ['data-plant-target' => 'ticket']
            ])
            ->add('percentageDiff',TextType::class, [
                'label' => 'Ticket Expected limit',
                'help' => '[percentageDiff]',
                'attr' => ['data-plant-target' => 'ticket'],
                'empty_data' => '',
            ])
            ->add('weatherTicket', SwitchType::class, [
                'label' => 'Activate Weather Ticket',
                'help' => '[WeatherTicket]',
                'attr' => ['data-plant-target' => 'ticket']
            ])
            ->add('kpiTicket', SwitchType::class, [
                'label' => 'Activate kpi Ticket',
                'help' => '[kpi Ticket]',
                'attr' => ['data-plant-target' => 'ticket']
            ])
            ->add('gridTicket', SwitchType::class, [
                'label' => 'Activate Grid Ticket',
                'help' => '[Grid Ticket]',
                'attr' => ['data-plant-target' => 'ticket']
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
                'placeholder' => 'Please Choose',
                'empty_data' => 'No',
            ])
            ->add('anlHidePlant', ChoiceType::class, [
                'label' => 'Anlage komplett ausblenden (Eigner und Admins)',
                'help' => '[anlHidePlant]',
                'label_html' => true,
                'choices' => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'Yes',
            ])

            ->add('anlMute', ChoiceType::class, [
                'label' => 'Anlage stummschalten (keine Fehlermeldungen senden) (inaktiv)',
                'help' => '[anlMute]',
                'label_html' => true,
                'choices' => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'No',
            ])

            ->add('anlMuteUntil', DateType::class, [
                'label' => 'Anlage stumgeschaltet bis:',
                'help' => '[anlMuteUntil]',
                'label_html' => true,
                'disabled' => true,
                'widget' => 'single_text',
            ])

            ->add('useCosPhi', SwitchType::class, [
                'label' => 'Aktiviere cosPhi',
                'help' => '[useCosPhi]',
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Anlage::class,
            'anlagenId' => '',
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}

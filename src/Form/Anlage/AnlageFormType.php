<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\WeatherStation;
use App\Form\EconomimcVarNamesFormType;
use App\Form\EventMail\EventMailListEmbeddedFormType;
use App\Form\Groups\GroupsListEmbeddedFormType;
use App\Form\GroupsAc\AcGroupsListEmbeddedFormType;
use App\Form\Type\SwitchType;
use App\Helper\G4NTrait;
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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Security\Core\Security;

class AnlageFormType extends AbstractType
{
    use G4NTrait;

    private Security $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prArray = [
            'No Cust PR'                => 'no',
            'Groningen'                 => 'Groningen',
            'Veendam'                   => 'Veendam',
            'Lelystad (Temp Korrektur)' => 'Lelystad',
        ];
        $epcReportArry = [
            'Kein Bericht'      => 'no',
            'PR Garantie'       => 'prGuarantee',
            'Ertrags Garantie'  => 'yieldGuarantee',
        ];
        $pldDiviorArray = [
            'Expected Energy'               => 'expected',
            'Guaranteed Expected Energy'    => 'guaranteedExpected',
        ];
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $builder
            ################################################
            ####                General                 ####
            ################################################

            ###### Plant Location #######
            ->add('eigner', EntityType::class, [
                'label'         => 'Eigner',
                'help'          => '[eigner]',
                'class'         => Eigner::class,
                'choice_label'  => 'firma',
                'required'      => true,
                'disabled'      => !$isDeveloper,
            ])
            ->add('anlName', TextType::class, [
                'label'         => 'Anlagen Name',
                'help'          => '[anlName]',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('projektNr', TextType::class, [
                'label'         => 'Projekt Nummer',
                'help'          => '[projektNr]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlStrasse', TextType::class, [
                'label'         => 'Strasse',
                'help'          => '[anlStrasse]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlPlz', TextType::class, [
                'label'         => 'PLZ',
                'help'          => '[anlPlz]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlOrt', TextType::class, [
                'label'         => 'Ort',
                'help'          => '[anlOrt]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('country', TextType::class, [
                'label'         => 'Land als Kürzel (de, nl, ...)',
                'help'          => '[country]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlGeoLat', TextType::class, [
                'label'         => 'Geografische Breite (Latitude) [Dezimalgrad]',
                'help'          => '[anlGeoLat]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlGeoLon', TextType::class, [
                'label'         => 'Geografische Länge (Longitude) [Dezimalgrad]',
                'help'          => '[anlGeoLon]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('notes', TextareaType::class, [
                'label'         => 'Notizen zur Anlage',
                'attr'          => ['rows' => '6'],
                'empty_data'    => '',
                'required'      => false,
            ])

            ###### Plant Base Configuration #######
            ->add('anlIntnr', TextType::class, [
                'label'         => 'Datenbankkennung',
                'help'          => '[anlIntnr]',
                'empty_data'    => '',
                'required'      => true,
                'disabled'      => !$isDeveloper,
            ])

            ->add('anlType', ChoiceType::class, [
                'label'         => 'Anlagen Typ',
                'help'          => '[anlType]',
                'choices'       => ['String WR' => 'string', 'ZWR' => 'zwr', 'Master Slave' => 'masterslave'],
                'placeholder'   => 'Please Choose',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('anlBetrieb', null, [
                'label'         => 'In Betrieb seit:',
                'help'          => '[anlBetrieb]',
                'widget'        => 'single_text',
                'input'         => 'datetime',

            ])
            ->add('anlZeitzone', ChoiceType::class, [
                'label'         => 'Zeit Korrektur Anlage',
                'help'          => '[anlZeitzone]',
                'choices'       => self::timeArray(),
                'placeholder'   => 'Please Choose',
                'empty_data'    => '+0',
            ])
            ->add('anlInputDaily', ChoiceType::class, [
                'label'         => 'Nur einmal am Tag neue Daten',
                'help'          => '[anlInputDaily]',
                'choices'       => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder'   => 'Please Choose',
                'empty_data'    => 'No',
            ])
            ->add('configType', ChoiceType::class, [
                'label'         => 'Configuration der Anlage',
                'help'          => '[configType]',
                'choices'       => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'placeholder'   => 'Please Choose',
                'empty_data'    => 1,
                'disabled'      => !$isDeveloper,
            ]);

        if ($this->security->isGranted('ROLE_DEV')) {
            $builder
                ->add('useNewDcSchema', ChoiceType::class, [
                    'label'         => 'Neues DC Database Schema (separate Tabelle für DC IST)',
                    'help'          => '[useNewDcSchema]',
                    'choices'       => ['Yes' => '1', 'No' => '0'],
                    'empty_data'    => '0',
                    'expanded'      => false,
                    'multiple'      => false,
                ])
            ;
        }

        $builder
            ###### WeatherStation #######
            ->add('WeatherStation', EntityType::class, [
                'label'         => 'Wetterstation',
                'help'          => '[WeatherStation]',
                'class'         => WeatherStation::class,
                'choice_label'  => function(WeatherStation $station) {return sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation());},
                'required'      => true,
                'disabled'      => !$isDeveloper,
            ])
            ->add('useLowerIrrForExpected', ChoiceType::class, [
                'label'         => 'Benutze \'IrrLower\' für die Berechnung Expected',
                'help'          => '[useLowerIrrForExpected]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
            ])


            ################################################
            ####       Plant Data / Configuration       ####
            ################################################
            ->add('kwPeak', TextType::class, [
                'label'         => 'Anlagenleistung [kWp]',
                'help'          => '[power]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '',
            ])
            ->add('kwPeakPvSyst', TextType::class, [
                'label'         => 'Anlagenleistung PVSYST [kWp]',
                'help'          => '[kwPeakPvSyst]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '',
            ])


            ->add('useCustPRAlgorithm', ChoiceType::class, [
                'label'         => 'Wähle und aktiviere Kundenspezifische PR Berechnung',
                'help'          => '[useCustPRAlgorithm]',
                'choices'       => $prArray,
                'empty_data'    => 'no',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('tempCorrCellTypeAvg', TextType::class, [
                'label'         => 't Cell AVG (nur für PR Algor. Lelystadt, wenn 0 dann ohne Temperatur korrektur)',
                'help'          => '[tempCorrCellTypeAvg]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('tempCorrGamma', TextType::class, [
                'label'         => 'Gamma',
                'help'          => '[tempCorrGamma]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '-0.4',
                'disabled'      => !$isDeveloper,
            ])
            ->add('tempCorrA', TextType::class, [
                'label'         => 'A',
                'help'          => '[tempCorrA]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '-3.56',
                'disabled'      => !$isDeveloper,
            ])
            ->add('tempCorrB', TextType::class, [
                'label'         => 'B',
                'help'          => '[tempCorrB]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '-0.0750',
                'disabled'      => !$isDeveloper,
            ])
            ->add('tempCorrDeltaTCnd', TextType::class, [
                'label'         => 'Delta T CND',
                'help'          => '[tempCorrDeltaTCnd]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '3.0',
                'disabled'      => !$isDeveloper,
            ])
            ->add('threshold1PA', TextType::class, [
                'label'         => 'unterer Schwellwert (normal 0) [Watt] ',
                'help'          => '[threshold1PA] (ti,theo / Schwellwert 1)',
                'label_html'    => true,
            ])
            ->add('threshold2PA', TextType::class, [
                'label'         => 'min Irr. ab der PA berechnet werden soll [Watt] ',
                'help'          => '[threshold2PA] (ti / Schwellwert 2)',
                'label_html'    => true,
            ])
            ->add('useGridMeterDayData', ChoiceType::class, [
                'label'         => 'Nutze externe GridMeter Daten',
                'help'          => '[useGridMeterDayData]',
                'label_html'    => true,
                'choices'       => ['No' => '0', 'Yes' => '1'],
                //'placeholder'   => 'Please Choose',
                'empty_data'    => 'No',
            ])
            ->add('contractualAvailability', TextType::class, [
                'label'         => 'Verfügbarkeit in %',
                'help'          => '[contractualAvailability]',
                'label_html'    => true,
                'empty_data'    => '0',
            ])
            ->add('contractualPR', TextType::class, [
                'label'         => 'PR (Garantie) in %',
                'help'          => '[contractualPR]',
                'label_html'    => true,
                'empty_data'    => '0',
            ])
            ->add('contractualPower', TextType::class, [
                'label'         => 'Jahres Leistung in [kWh]',
                'help'          => '[contractualPower]',
                'label_html'    => true,
                'empty_data'    => '',
            ])
            ->add('designPR', TextType::class, [
                'label'         => 'PR Design (pvSyst) [%]',
                'help'          => '[designPR]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '',
            ])
            ->add('isOstWestAnlage', ChoiceType::class, [
                'label'         => 'Anlage hat Ost/West Ausrichtung',
                'help'          => '[isOstWestAnlage]',
                'label_html'    => true,
                'choices'       => ['No' => '0', 'Yes' => '1'],
                //'placeholder'   => 'Please Choose',
                'empty_data'    => 'No',
            ])
            ->add('powerEast', TextType::class, [
                'label'         => 'Anlagenleistung [kWp] Osten',
                'help'          => '[powerEast]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '',
            ])
            ->add('powerWest', TextType::class, [
                'label'         => 'Anlagenleistung [kWp] Westen',
                'help'          => '[powerWest]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '',
            ])
            ->add('pacDate', null, [
                'label'         => 'PAC Datum',
                'help'          => '[pacDate]',
                'label_html'    => true,
                'widget'        => 'single_text',
                'input'         => 'datetime',
                //'empty_data'    => new \DateTime('now'),
            ])
            ->add('pacDateEnd', null, [
                'label'         => 'PAC Zeitraum Ende',
                'help'          => '[pacDate]',
                'label_html'    => true,
                'widget'        => 'single_text',
                'input'         => 'datetime',
                //'empty_data'    => new \DateTime('now'),
            ])
            ->add('usePac', ChoiceType::class, [
                'label'         => 'Use PAC Date',
                'help'          => '[usePac]',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'empty_data'    => '0',
            ])
            ->add('pacDuration',TextType::class, [
                'label'         => 'PAC Zeitraums in Monaten',
                'help'          => '[pacDuration]',
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('facDateStart', null, [
                'label'         => 'FAC Zeitraum Start',
                'help'          => '[facDateStart]',
                'required'      => false,
                'widget'        => 'single_text',
                'input'         => 'datetime',
            ])
            ->add('facDate', null, [
                'label'         => 'FAC Datum',
                'help'          => '[facDate]',
                'label_html'    => true,
                'widget'        => 'single_text',
                'input'         => 'datetime',
            ])
            ->add('epcReportStart', null, [
                'label'         => 'EPC Report Zeitraum Start',
                'help'          => '[epcReportStart]',
                'required'      => false,
                'widget'        => 'single_text',
                'input'         => 'datetime',
            ])
            ->add('epcReportEnd', null, [
                'label'         => 'EPC Report Datum',
                'help'          => '[epcReportEnd]',
                'label_html'    => true,
                'widget'        => 'single_text',
                'input'         => 'datetime',
            ])
            ->add('lid', TextType::class, [
                'label'         => 'Verlust Risikoabsch. (LID) [%]',
                'help'          => '[lid]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('annualDegradation', TextType::class, [
                'label'         => 'Verlust Annual Degrad. [%]',
                'help'          => '[annualDegradation]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('pldPR', TextType::class, [
                'label'         => 'PLD PR (VE) [EUR/kWh]',
                'help'          => '[pldPR]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('pldNPValue', TextType::class, [
                'label'         => 'PLD NP Wert [%]',
                'help'          => '[pldNPValue]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('pldYield', TextType::class, [
                'label'         => 'PLD Ertrag [faktor]',
                'help'          => '[pldYield]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('pldDivisor', ChoiceType::class, [
                'label'         => 'PLD Divisor (Welcher Wert soll als Divisior in PLD Formel genutzt werden)',
                'help'          => '[pldDivisor]',
                'label_html'    => true,
                'choices'       => $pldDiviorArray,
                'empty_data'    => 'expected',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('transformerTee', TextType::class, [
                'label'         => 'Abschalg Trafoverlust [%]',
                'help'          => '[transformerTee]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('guaranteeTee', TextType::class, [
                'label'         => 'Abschlag Garantie [%]',
                'help'          => '[guaranteeTee]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('hasDc', ChoiceType::class, [
                'label'         => 'Anlage hat DC Daten',
                'help'          => '[hasDc]',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'empty_data'    => '0',
            ])
            ->add('hasStrings', ChoiceType::class, [
                'label'         => 'Anlage hat String Daten',
                'help'          => '[hasStrings]',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'empty_data'    => '0',
            ])
            ->add('hasPannelTemp', ChoiceType::class, [
                'label'         => 'Anlage hat Pannel Temperatur',
                'help'          => '[hasPannelTemp]',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'empty_data'    => '0',
            ])
            ->add('useDayForecast', ChoiceType::class, [
                'label'         => 'use Forecast by Day',
                'help'          => '[useDayForecast]',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'required'      => false,
                'empty_data'    => 0,
            ])
            ->add('degradationForecast', TextType::class, [
                'label'         => 'Degradation, only Forecast [%]',
                'help'          => '[degradationForecast]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ->add('lossesForecast', TextType::class, [
                'label'         => 'Losses, only Forecast [%]',
                'help'          => '[lossesForecast]',
                'label_html'    => true,
                'required'      => false,
                'empty_data'    => '0',
            ])
            ################################################
            ####               Reports                  ####
            ################################################

            ->add('epcReportType', ChoiceType::class, [
                'label'         => 'Welchen EPC Report',
                'help'          => '[epcReportType]',
                'choices'       => $epcReportArry,
                //'placeholder'   => 'Please Choose',
                'empty_data'    => 'no',
                'expanded'      => false,
                'multiple'      => false,
            ])

            ################################################
            ####              Settings                  ####
            ################################################
            ->add('anlView', ChoiceType::class, [
                'label'         => 'Anlage für Eigner:',
                'help'          => '[anlView]',
                'choices'       => ['aktiv' => 'Yes', 'deaktiviert' => 'No'],
                'placeholder'   => 'Please Choose',
                'empty_data'    => 'No',
            ])
            ->add('anlHidePlant', ChoiceType::class, [
                'label'         => 'Anlage komplett ausblenden (Eigner und Admins)',
                'help'          => '[anlHidePlant]',
                'label_html'    => true,
                'choices'       => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder'   => 'Please Choose',
                'empty_data'    => 'Yes',
            ])
            ->add('anlMute', ChoiceType::class, [
                'label'         => 'Anlage stummschalten (keine Fehlermeldungen senden) (inaktiv)',
                'help'          => '[anlMute]',
                'label_html'    => true,
                'choices'       => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder'   => 'Please Choose',
                'empty_data'    => 'No',
            ])
            ->add('anlMuteUntil', DateType::class, [
                'label'         => 'Anlage stumgeschaltet bis:',
                'help'          => '[anlMuteUntil]',
                'label_html'    => true,
                'disabled'      => true,
                'widget' => 'single_text',
            ])
            ->add('useCosPhi', ChoiceType::class, [
                'label'         => 'Aktiviere cosPhi',
                'help'          => '[useCosPhi]',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'empty_data'    => '0',
            ])
            ->add('calcPR', ChoiceType::class, [
                'label'         => 'PR und andere Werte berechnen (wenn Anlage noch nicht final eingerichtet, bitte auf \'No\' stellen)',
                'help'          => '[calcPR]',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'empty_data'    => '0',
            ])


            ################################################
            ####         Configuartion Backend          ####
            ################################################
            ->add('showOnlyUpperIrr', ChoiceType::class, [
                'label'         => 'Zeige nur eine Strahlungskennlinie',
                'help'          => '[showOnlyUpperIrr]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showStringCharts', ChoiceType::class, [
                'help'          => '[showStringCharts]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showAvailability', ChoiceType::class, [
                'help'          => '[showAvailability]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showAvailabilitySecond', ChoiceType::class, [
                'help'          => '[showAvailabilitySecond]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showInverterPerformance', ChoiceType::class, [
                'help'          => '[showInverterPerformance]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showEvuDiag', ChoiceType::class, [
                'help'          => '[showEvuDiag]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showInverterOutDiag', ChoiceType::class, [
                'help'          => '[showInverterOutDiag]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showCosPhiDiag', ChoiceType::class, [
                'help'          => '[showCosPhiDiag]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showCosPhiPowerDiag', ChoiceType::class, [
                'help'          => '[showCosPhiPowerDiag]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showGraphDcInverter', ChoiceType::class, [
                'help'          => '[showGraphDcInverter]',
                'label'         => 'Zeige Diagramm \'DC - Inverter\'',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showGraphDcCurrInv', ChoiceType::class, [
                'label'         => 'Zeige Diagramm \'DC - Current Inverter\'',
                'help'          => '[showGraphDcCurrInv]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showGraphDcCurrGrp', ChoiceType::class, [
                'label'         => 'Zeige Diagramm \'DC - Current Group\'',
                'help'          => '[showGraphDcCurrGrp]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showGraphVoltGrp', ChoiceType::class, [
                'label'         => 'Zeige Diagramm \'DC - Voltage Group\'',
                'help'          => '[showGraphVoltGrp]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showGraphIrrPlant', ChoiceType::class, [
                'label'         => 'Zeige Diagramm \'Irradiation Plant\'',
                'help'          => '[showGraphIrrPlant]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showPR', ChoiceType::class, [
                'label'         => 'Zeige Diagramm \'PR\'',
                'help'          => '[showPR]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showPvSyst', ChoiceType::class, [
                'label'         => 'Zeige Tabelle \'PvSyst\'',
                'help'          => '[showPvSyst]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            ->add('showForecast', ChoiceType::class, [
                'label'         => 'Zeige Forecast',
                'help'          => '[showForecast]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'expanded'      => false,
                'multiple'      => false,
            ])
            /*
            ->add('showForecast', CheckboxType::class, [
                'label'         => 'Zeige Forecast',
                'help'          => '[showForecast]',
                'false_values'  => ['0', 'no', 'No', 'NO'],
                'attr'          => ['class' => 'switch-input'],

            ])
            */

            ################################################
            ####              Relations                 ####
            ################################################
            ->add('modules', CollectionType::class, [
                'entry_type'    => ModulesListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ->add('timesConfigs', CollectionType::class, [
                'entry_type'    => TimeConfigListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])

            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################
            ->add('save', SubmitType::class, [
                'label' => 'Save Plant',
                'attr'  => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close Plant',
                'attr'  => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close without save',
                'attr'  => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
            ->add('savecreatedb', SubmitType::class, [
                'label'         => 'Save and Create Databases',
                'attr'  => ['class' => 'secondary small', 'formnovalidate' => 'formnovalidate'],
            ])
        ;

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => Anlage::class,
            'anlagenId'     => '',
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}

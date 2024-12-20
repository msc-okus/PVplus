<?php

namespace App\Form\Anlage;

use App\Entity\AnlageSettings;
use App\Helper\G4NTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use App\Helper\PVPNameArraysTrait;
use App\Repository\ApiConfigRepository;
use App\Form\Type\SwitchType;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

class AnlageSettingsFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct(
        private readonly ApiConfigRepository $apiConfigRepository,
        private readonly TranslatorInterface $translator,
        private readonly Security $security
    ){
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //cgreate Options for API-Settings Select
        $apiConfigs = $this->apiConfigRepository->findByOwnerId($_SESSION['tempOwnerId']);

        for($i=0; $i < count($apiConfigs); $i++) {
            $apiConfigsArray[$apiConfigs[$i]->getConfigName()] = $apiConfigs[$i]->getId();
        }

        $builder
            ######## Handling Departments ########
            ->add('disableDep0', SwitchType::class, [
                'label'     => 'could not be disabled',
                'help'      => '',
                'attr'      => ['disabled' => 'disabled'],
                'data'      => false,
                'mapped'    => false,
            ])
            ->add('disableDep1', SwitchType::class, [
                'label'     => 'disable',
                'help'      => '[disableDep1]',
            ])
            ->add('disableDep2', SwitchType::class, [
                'label'     => 'disable',
                'help'      => '[disableDep2]',
            ])
            ->add('disableDep3', SwitchType::class, [
                'label'     => 'disable',
                'help'      => '[disableDep3]',
            ])

            ->add('enablePADep0', SwitchType::class, [
                'label'     => 'enabled PA Calculation',
                'help'      => 'always enabled',
                'attr'      => ['disabled' => 'disabled'],
                'data'      => true,
                'mapped'    => false,
            ])
            ->add('enablePADep1', SwitchType::class, [
                'label'     => 'enable PA Calculation',
                'help'      => '[enablePADep1]',
            ])
            ->add('enablePADep2', SwitchType::class, [
                'label'     => 'enable PA Calculation',
                'help'      => '[enablePADep2]',
            ])
            ->add('enablePADep3', SwitchType::class, [
                'label'     => 'enable PA Calculation',
                'help'      => '[enablePADep3]',
            ])

            ######## AC Charts ########
            ->add('chartAC1', SwitchType::class, [
                'label'     => 'View AC Power -Actual & Expected, Plant',
                'help'      => '[On / Off - ChartAC1]'
            ])
            ->add('chartAC2', SwitchType::class, [
                'label'     => 'View AC Power -Actual & Expected, Overview',
                'help'      => '[On / Off - ChartAC2]'
            ])
            ->add('chartAC3', SwitchType::class, [
                'label'     => 'View AC Power -Actual & Expected, Inverter',
                'help'      => '[On / Off - ChartAC3]'
            ])
            ->add('chartAC9', SwitchType::class, [
                'label'     => 'View AC Power - Inverter (Normalized)',
                'help'      => '[On / Off]'
            ])
            ->add('chartAC4', SwitchType::class, [
                'label'     => 'View AC Inverter (Bar Chart)',
                'help'      => '[chartAC4]',
            ])
            ->add('chartAC5', SwitchType::class, [
                'label'     => 'View AC Voltage - Inverter',
                'help'      => '[cOn / Off - ChartAC5]'
            ])
            ->add('chartAC6', SwitchType::class, [
                'label'     => 'View AC Current - Inverter',
                'help'      => '[On / Off - ChartAC6]'
            ])
            ->add('chartAC7', SwitchType::class, [
                'label'     => 'View AC Frequency - Inverter',
                'help'      => '[On / Off - ChartAC7]'
            ])
            ->add('chartAC8', SwitchType::class, [
                'label'     => 'View AC Reactive Power - Inverter',
                'help'      => '[On / Off - ChartAC8]'
            ])

            ######## DC Charts ########
            ->add('chartDC1', SwitchType::class, [
                'label'     => 'View DC Power -Actual & Expected, Plant',
                'help'      => '[On / Off - ChartDC1]'
            ])
            ->add('chartDC2', SwitchType::class, [
                'label'     => 'View DC Power -Actual & Expected, Overview',
                'help'      => '[cOn / Off - ChartDC2]'
            ])
            ->add('chartDC3', SwitchType::class, [
                'label'     => 'View DC Power -Actual & Expected, Inverter',
                'help'      => '[On / Off - ChartDC3]'
            ])
            ->add('chartDC4', SwitchType::class, [
                'label'     => 'View BARCHART ?? DC4N',
                'help'      => '[On / Off - ChartDC4]',
            ])
            ->add('chartDC5', SwitchType::class, [
                'label'     => 'View NND DC5',
                'help'      => '[On / Off - ChartDC5]'
            ])
            ->add('chartDC6', SwitchType::class, [
                'label'     => 'View NN DC6',
                'help'      => '[On / Off - ChartDC6]'
            ])
            
            ###### Analyses ######
            ->add('chartAnalyse1', SwitchType::class, [
                'label'     => 'View Availability (Case Data)',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse2', SwitchType::class, [
                'label'     => 'View PA and PR',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse3', SwitchType::class, [
                'label'     => 'View Forecast',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse4', SwitchType::class, [
                'label'     => 'View Inverter PR Heatmap',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse5', SwitchType::class, [
                'label'     => 'View Inverter Temperature Heatmap',
                'help'      => '[On / Off]',
            ])
            ->add('chartAnalyse6', SwitchType::class, [
                'label'     => 'View DC Current Heatmap',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse7', SwitchType::class, [
                'label'     => 'View Analyse - actual vs. expected',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse8', SwitchType::class, [
                'label'     => 'View Analyse - actual vs. expected to Temp.',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse9', SwitchType::class, [
                'label'     => 'View Analyse - actual vs. expected to Irr.',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse10', SwitchType::class, [
                'label'     => 'View Forecast PR',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse11', SwitchType::class, [
                'label'     => 'Forecast 3',
                'help'      => '[On / Off]'
            ])
            ->add('chartAnalyse12', SwitchType::class, [
                'label'     => 'View Forecast Day Ahead',
                'help'      => '[On / Off]'
            ])
            ###### Current ######
            ->add('chartCurr1', SwitchType::class, [
                'label'     => 'View DC Current, Overview',
                'help'      => '[On / Off]'
            ])
            ->add('chartCurr2', SwitchType::class, [
                'label'     => 'DC Current, Inverter (not ready)',
                'help'      => '[On / Off]'
            ])
            ->add('chartCurr3', SwitchType::class, [
                'label'     => 'DC Current Overview (Normalized)',
                'help'      => '[On / Off]'
            ])
            ###### Voltage ######
            ->add('chartVolt1', SwitchType::class, [
                'label'     => 'View DC Voltage, Overview',
                'help'      => '[On / Off ChartVolt1]'
            ])
            ->add('chartVolt2', SwitchType::class, [
                'label'     => 'View Voltage 2 (inaktiv)',
                'help'      => '[On / Off ChartVolt2]'
            ])
            ->add('chartVolt3', SwitchType::class, [
                'label'     => 'View Voltage 3 (inaktiv)',
                'help'      => '[On / Off ChartVolt3]'
            ])
            ###### Sensor ######
            ->add('chartSensor1', SwitchType::class, [
                'label'     => 'Irradiation (Std)',
                'help'      => '[chartSensor1]'
            ])
            ->add('chartSensor2', SwitchType::class, [
                'label'     => 'Irradiation All',
                'help'      => '[chartSensor2]'
            ])
            ->add('chartSensor3', SwitchType::class, [
                'label'     => 'Temperature & Wind',
                'help'      => '[chartSensor3]'
            ])
            ->add('chartSensor4', SwitchType::class, [
                'label'     => 'NN.  (inaktiv)',
                'help'      => '[chartSensor4]'
            ])

            ->add('usePpcTicketToReplacePvSyst', SwitchType::class,[
                'label'     => 'Use PPC Tickets for PVSyst Replace',
                'help'      => '[usePpcTicketToReplacePvSyst]<br>To use this its nessesary to import the PVSyst hour data.'
            ])

            ->add('epxCalculationByCurrent', SwitchType::class, [
                'label'     => 'Calculate \'expected\' with (current * voltage)',
                'help'      => '[epxCalculationByCurrent]'
            ])


            ###### Import ######
            ->add('apiConfig', ChoiceType::class, [
                'choices'       => $apiConfigsArray,
                'label'     => 'Choose API-Setting',
                'placeholder'   => 'please Select',
                'required'      => false,
                'help'      => '[settings.importType]<br>Chose wich API-Setting is used for impoert',
                'attr' => ['style' => 'width: 150px']
            ])

            ->add('symfonyImport', SwitchType::class, [
                'label'     => 'Import Data with Symphony',
                'help'      => '[symfonyImport]<br>Enable Import Data with Symphony'
            ])

            ->add('huaweiImport', SwitchType::class, [
                'label'     => 'Import Data from Huawei (Huawei)',
                'help'      => '[symfonyImport]<br>Enable Import Data from Huawei'
            ])

            ->add('useSensorsData', SwitchType::class, [
                'label'     => 'Import Sensors Data into new Table',
                'help'      => '[useSensorsData]<br>Import Sensors Data into new Table like db__pv_sensors_data_CX...'
            ])

            ->add('sensorsInBasics', SwitchType::class, [
                'label'     => 'This plant have sensors in VCOM/Basics',
                'help'      => '[sensorsInBasics]<br>This plant have sensors in Vcom/Basics'
            ])

            ->add('sensorsFromSatelite', SwitchType::class, [
                'label'     => 'This plant have sensors from an satelite',
                'help'      => 'This plant have sensors from an satelite'
            ])

            ->add('importType', ChoiceType::class, [
                'choices'       => self::importTypes(),
                'placeholder'   => 'please Select',
                'required'      => false,
                'help'      => '[importType]<br>Chose the plant have Stringboxes or inverters only',
            ])

            ->add('stringboxesUnits', IntegerType::class, [
                'label' => 'Stringboxes Units',
                'help' => '[stringboxesUnits]<br>How many Units have a stringbox? (look in the Response from VCOM)',
                'empty_data' => '',
                'required' => false,
            ])

            ->add('invertersUnits', IntegerType::class, [
                'label' => 'Inverters Units',
                'help' => '[invertersUnits]<br>How many Units have a inverter? (look in the Response from VCOM)',
                'empty_data' => '',
                'required' => false,
            ])

            ->add('dataDelay', ChoiceType::class, [
                'label' => 'Data Delay(max 24 hours)',
                'multiple' => false,
                'choices'       => self::delayedDataValus(),
                'preferred_choices' => array(1),
                'help' => '[dataDelay]<br>use this if data from vcom or FTP-Push are delayed as normal case)',
                'required' => false,
                'empty_data' => '0'
            ])

            ###### Analysis ######
            ->add('activateAnalysis', SwitchType::class, [
                'label'     => 'Enable the Analysis tools for this plant',
            ])
            ->add('stringAnalysis', SwitchType::class, [
                'label'     => 'Enable String Analysis ',
            ])

            ###### Settings Auto Performance Ticket ######
            ->add('ppcAutoTicketBehavior', ChoiceType::class, [
                'multiple' => false,
                'label' => 'PPC Ticket Behavior',
                'help' => '[ppcAutoTicketBehavior]<br>Define if and which PerformanceTicket should be autogenerated.',
                'choices' => ['do nothing' => 'nothing', 'create replace' => 'replace', 'create exclude' => 'exclude'],
                'preferred_choices' => [1],
                'empty_data' => 'nothing',
                'attr' => ['data-action' => 'anlage-ppcalert-settings#onChangeBehavior']
            ])
            ->add('ppcAutoTicketScope', ChoiceType::class, [
                'label' => 'Scope',
                'attr' => ['class' => 'js-settings'],
                'choices' => $this->scope(),
                'expanded' => true,
                'multiple' => true,
                'label_attr' => ['class' => 'checkbox-inline']
            ])
            ->add('ppcAutoTicketReplaceBy',ChoiceType::class, [
                'multiple' => false,
                'attr' => ['class' => 'js-settings js-disable-exclude','data-action' => 'anlage-ppcalert-settings#onChangeReplaceBy'],
                'label' => 'PPC Ticket Replace',
                'choices' => ['replace with G4N Expected' => 'g4n_exp', 'replace with PvSyst' => 'pvsyst'],
                'help' => '[ppcAutoTicketReplaceBy]<br>Define which data should be used to repalce',
                'placeholder' => 'please Select',
            ])
            ->add('ppcAutoTicketReplaceIrr', SwitchType::class, [
                'label' => 'replace Irradiation',
                'attr' => ['class' => 'js-settings js-disable-exclude js-disable-replace'],
                'help' => '[ppcAutoTicketReplaceIrr]<br>make sens only for PvSyst',
            ])
            ->add('ppcAutoTicketUseHour', SwitchType::class, [
                'label' => 'use Houre Values',
                'attr' => ['class' => 'js-settings js-disable-replace'],
                'help' => '[ppcAutoTicketUseHour]<br>make sens only for PvSyst',
            ])
            ->add('ppcAutoTicketPaBehavior',ChoiceType::class, [
                'multiple' => false,
                'attr' => ['class' => 'js-settings'],
                'label' => 'Behavior for PA',
                'choices' => ['skip' => 'skip', 'replace outage for tiFM' => 'replace'],
                'help' => '[ppcAutoTicketPaBehavior]<br>Define how PA should be handled',
                'placeholder' => 'please Select',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageSettings::class,
            'required' => false,
        ]);
    }

}

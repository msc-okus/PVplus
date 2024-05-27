<?php

namespace App\Form\Anlage;

use App\Entity\AnlageSettings;
use App\Form\Type\SwitchType;
use App\Helper\PVPNameArraysTrait;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormTypeInterface;
class AnlageSettingsFormType extends AbstractType
{
    use PVPNameArraysTrait;
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            ->add('symfonyImport', SwitchType::class, [
                'label'     => 'Import Data with Symphony',
                'help'      => 'Enable Import Data with Symphony without the old php skript files'
            ])

            ->add('useSensorsData', SwitchType::class, [
                'label'     => 'Import Sensors Data into new Table',
                'help'      => 'Import Sensors Data into new Table like db__pv_sensors_data_CX...'
            ])

            ->add('sensorsInBasics', SwitchType::class, [
                'label'     => 'This plant have sensors in VCOM/Basics',
                'help'      => 'This plant have sensors in Vcom/Basics'
            ])

            ->add('importType', ChoiceType::class, [
                'choices'       => self::importTypes(),
                'placeholder'   => 'please Select',
                'required'      => false,
                'help'      => 'Chose the plant have Stringboxes or inverters only'
            ])

            ->add('stringboxesUnits', IntegerType::class, [
                'label' => 'Stringboxes Units',
                'help' => 'How many Units have a stringbox? (look in the Response from VCOM)',
                'empty_data' => '',
                'required' => false,
            ])

            ->add('invertersUnits', IntegerType::class, [
                'label' => 'Inverters Units',
                'help' => 'How many Units have a inverter? (look in the Response from VCOM)',
                'empty_data' => '',
                'required' => false,
            ])

            ->add('dataDelay', IntegerType::class, [
                'label' => 'Data Delay(max 24 hours)',
                'help' => 'use this if data from vcom or FTP-Push are delayed as normal case)',
                'empty_data' => '0',
                'required' => false,
                'attr' => array('max' < 25,
                    'maxlength' => 2)
            ])

            ###### Analysis ######
            ->add('activateAnalysis', SwitchType::class, [
                'label'     => 'Enable the Analysis tools for this plant',
            ])
            ->add('stringAnalysis', SwitchType::class, [
                'label'     => 'Enable String Analysis ',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageSettings::class,
        ]);
    }
}

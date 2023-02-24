<?php

namespace App\Form\Anlage;

use App\Entity\AnlageSettings;
use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnlageSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ######## AC Charts ########
            ->add('chartAC1', SwitchType::class, [
                'label'     => 'AC Power -Actual & Expected, Plant',
                'help'      => '[chartAC1]'
            ])
            ->add('chartAC2', SwitchType::class, [
                'label'     => 'AC Power -Actual & Expected, Overview',
                'help'      => '[chartAC2]'
            ])
            ->add('chartAC3', SwitchType::class, [
                'label'     => 'AC Power -Actual & Expected, Inverter',
                'help'      => '[chartAC3]'
            ])
            ->add('chartAC4', SwitchType::class, [
                'label'     => 'AC Inverter (Bar Chart)',
                'help'      => '[chartAC4]',
            ])
            ->add('chartAC5', SwitchType::class, [
                'label'     => 'AC Voltage - Inverter',
                'help'      => '[chartAC5]'
            ])
            ->add('chartAC6', SwitchType::class, [
                'label'     => 'AC Current - Inverter',
                'help'      => '[chartAC6]'
            ])
            ->add('chartAC7', SwitchType::class, [
                'label'     => 'AC Frequency - Inverter',
                'help'      => '[chartAC7]'
            ])
            ->add('chartAC8', SwitchType::class, [
                'label'     => 'AC Reactive Power - inverter',
                'help'      => '[chartAC8]'
            ])
            ->add('chartAC9', SwitchType::class, [
                'label'     => 'NN AC9',
                'help'      => '[chartAC8]'
            ])

            ######## DC Charts ########
            ->add('chartDC1', SwitchType::class, [
                'label'     => 'DC Power -Actual & Expected, Plant',
                'help'      => '[chartDC1]'
            ])
            ->add('chartDC2', SwitchType::class, [
                'label'     => 'DC Power -Actual & Expected, Overview',
                'help'      => '[chartDC2]'
            ])
            ->add('chartDC3', SwitchType::class, [
                'label'     => 'DC Power -Actual & Expected, Inverter',
                'help'      => '[chartDC3]'
            ])
            ->add('chartDC4', SwitchType::class, [
                'label'     => 'BARCHART ?? DC4N',
                'help'      => '[chartDC4]',
            ])
            ->add('chartDC5', SwitchType::class, [
                'label'     => 'NND DC5',
                'help'      => '[chartDC5]'
            ])
            ->add('chartDC6', SwitchType::class, [
                'label'     => 'NN DC6',
                'help'      => '[chartDC6]'
            ])
            
            ###### Analyses ######
            ->add('chartAnalyse1', SwitchType::class, [
                'label'     => 'Show Availability (Case Data)',
                'help'      => '[chartAnalyse1]'
            ])
            ->add('chartAnalyse2', SwitchType::class, [
                'label'     => 'Show PA and PR',
                'help'      => '[chartAnalyse2]'
            ])
            ->add('chartAnalyse3', SwitchType::class, [
                'label'     => 'Forecast',
                'help'      => '[chartAnalyse2]'
            ])
            ->add('chartAnalyse4', SwitchType::class, [
                'label'     => 'Inverter PR Heatmap',
                'help'      => '[chartAnalyse3]'
            ])
            ->add('chartAnalyse5', SwitchType::class, [
                'label'     => 'Inverter Temperature Heatmap',
                'help'      => '[chartAnalyse4]',
            ])
            ->add('chartAnalyse6', SwitchType::class, [
                'label'     => 'DC Current Heatmap',
                'help'      => '[chartAnalyse5]'
            ])
            ->add('chartAnalyse7', SwitchType::class, [
                'label'     => 'Analyse - actual vs. expected',
                'help'      => '[chartAnalyse6]'
            ])
            ->add('chartAnalyse8', SwitchType::class, [
                'label'     => 'Analyse - actual vs. expected to Temp.',
                'help'      => '[chartAnalyse7]'
            ])
            ->add('chartAnalyse9', SwitchType::class, [
                'label'     => 'Analyse - actual vs. expected to Irr.',
                'help'      => '[chartAnalyse8]'
            ])
            
            ###### Current ######
            ->add('chartCurr1', SwitchType::class, [
                'label'     => 'Curr1',
                'help'      => '[chartCurr1]'
            ])
            ->add('chartCurr2', SwitchType::class, [
                'label'     => 'Curr2',
                'help'      => '[chartCurr2]'
            ])
            ->add('chartCurr3', SwitchType::class, [
                'label'     => 'Curr3',
                'help'      => '[chartCurr3]'
            ])
            ###### Voltage ######
            ->add('chartVolt1', SwitchType::class, [
                'label'     => 'Voltage 1',
                'help'      => '[chartVolt1]'
            ])
            ->add('chartVolt2', SwitchType::class, [
                'label'     => 'Voltage 2',
                'help'      => '[chartVolt2]'
            ])
            ->add('chartVolt3', SwitchType::class, [
                'label'     => 'Voltage 3',
                'help'      => '[chartVolt3]'
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
                'label'     => 'Temperature',
                'help'      => '[chartSensor3]'
            ])
            ->add('chartSensor4', SwitchType::class, [
                'label'     => 'NN. ',
                'help'      => '[chartSensor4]'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageSettings::class,
        ]);
    }
}
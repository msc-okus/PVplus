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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageSettings::class,
        ]);
    }
}
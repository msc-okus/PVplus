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
                'help'      => '[chartAC4]'
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageSettings::class,
        ]);
    }
}
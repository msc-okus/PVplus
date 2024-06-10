<?php

namespace App\Form\Owner;

use App\Entity\OwnerFeatures;
use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class OwnerFeaturesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('simulatorName', TextType::class, [
                'label'     => 'Name of the simulator tool used',
                'help'      => '[simulatorName]<br>example: PVSyst, PV Sol, etc <br>default value = "Simulation"'
            ])
            ->add('aktDep1', SwitchType::class, [
                'label'     => 'Activate Department 1 (O&M)',
                'help'      => '[aktDep1]'
            ])
            ->add('aktDep2', SwitchType::class, [
                'label'     => 'Activate Department 2 (EPC)',
                'help'      => '[aktDep2]'
            ])
            ->add('aktDep3', SwitchType::class, [
                'label'     => 'Activate Department 3 (AM)',
                'help'      => '[aktDep3]'
            ])
            ->add('SplitInverter', SwitchType::class, [
                'label'     => 'Activate - Split by Inverter',
                'help'      => '[SplitInverter]<br>Ticket feature - Split ticket by inverter'
            ])
            ->add('SplitGap', SwitchType::class, [
                'label'     => 'Activate - Split by Time',
                'help'      => '[SplitGap]<br>Ticket feature - Split ticket by time'
            ])
            ->add('mroAktive', SwitchType::class, [
                'label'     => 'Activate - MRO ticket',
                'help'      => "[mroAktive]<br>Activate the 'MRO ticket' function",
            ])
            ->add('amStringAnalyseAktive', SwitchType::class, [
                'label'     => 'Activate - AM String Analyse',
                'help'      => '[amStringAnalyseAktive]<br>Activate the Asset Management String Analyse',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OwnerFeatures::class,
        ]);
    }
}
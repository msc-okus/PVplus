<?php

namespace App\Form\Owner;

use App\Entity\OwnerFeatures;
use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                'label'     => 'Aktivate Department 1 (O&M)',
                'help'      => '[aktDep1]'
            ])
            ->add('aktDep2', SwitchType::class, [
                'label'     => 'Aktivate Department 2 (EPC)',
                'help'      => '[aktDep2]'
            ])
            ->add('aktDep3', SwitchType::class, [
                'label'     => 'Aktivate Department 3 (AM)',
                'help'      => '[aktDep3]'
            ])
            ->add('SplitInverter', SwitchType::class, [
                'label'     => 'Split by Inverter',
                'help'      => '[SplitInverter]<br>Ticket feature - Split ticket by inverter (on/off)'
            ])
            ->add('SplitGap', SwitchType::class, [
                'label'     => 'Split by Time',
                'help'      => '[SplitGap]<br>Ticket feature - Split ticket by time (on/off)'
            ])
            ->add('mroAktive', SwitchType::class, [
                'label'     => 'Aktivate Maintenance Contact',
                'help'      => '[ManAktive]<br>Activate the maintenance contact function',
            ])
            ->add('amStringAnalyseAktive', SwitchType::class, [
                'label'     => 'AM String Analyse',
                'help'      => '[amStringAnalyseAktive]<br>Activate the Asset Management String Analyse',
            ])
            ->add('allow2fa', SwitchType::class, [
                'label'     => 'Allow 2fa for this Owner',
                'help'      => '[allow2fa]<br> 2fa = 2-factor authentication',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OwnerFeatures::class,
            'required' => false,
        ]);
    }
}
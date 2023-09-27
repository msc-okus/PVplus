<?php

namespace App\Form\Anlage;

use App\Entity\AnlageModulesDB;
use App\Form\Type\SwitchType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnlageModulesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'label' => 'Module Type',
            ])
            ->add('producer', TextType::class, [
                'label' => 'Producer',
                'empty_data' => '',
            ])
            ->add('power', TextType::class, [
                'label' => 'Power Wp',
                'empty_data' => '0',
            ])
            ->add('tempCoefCurrent', TextType::class, [
                'label' => 'Temp. Coef. Current',
                'empty_data' => '0',
            ])
            ->add('tempCoefVoltage', TextType::class, [
                'label' => 'Temp. Coef. Voltage',
                'empty_data' => '0',
            ])
            ->add('tempCoefPower', TextType::class, [
                'label' => 'Temp. Coef. Power',
                'empty_data' => '0',
            ])
            ->add('degradation', TextType::class, [
                'label' => 'Module degradation per Year',
                'empty_data' => '0',
            ])
            ->add('maxImpp', TextType::class, [
                'label' => 'Max Current MPP',
                'empty_data' => '0',
            ])
            ->add('maxUmpp', TextType::class, [
                'label' => 'Max Voltage MPP',
                'empty_data' => '0',
            ])
            ->add('maxPmpp', TextType::class, [
                'label' => 'Max Power MPP',
                'empty_data' => '0',
            ])

            ->add('operatorCurrentA', TextType::class, [
                'label' => 'Current A',
                'empty_data' => '0',
            ])
            ->add('operatorCurrentB', TextType::class, [
                'label' => 'Current B',
                'empty_data' => '0',
            ])
            ->add('operatorCurrentC', TextType::class, [
                'label' => 'Current C',
                'empty_data' => '0',
            ])
            ->add('operatorCurrentD', TextType::class, [
                'label' => 'Current D',
                'empty_data' => '0',
            ])
            ->add('operatorCurrentE', TextType::class, [
                'label' => 'Current E',
                'empty_data' => '0',
            ])
            ->add('operatorCurrentHighA', TextType::class, [
                'label' => 'CurrentHigh A (>200W)',
                'empty_data' => '0',
            ])

            ->add('operatorPowerA', TextType::class, [
                'label' => 'Power A',
                'empty_data' => '0',
            ])
            ->add('operatorPowerB', TextType::class, [
                'label' => 'Power B',
                'empty_data' => '0',
            ])
            ->add('operatorPowerC', TextType::class, [
                'label' => 'Power C',
                'empty_data' => '0',
            ])
            ->add('operatorPowerD', TextType::class, [
                'label' => 'Power D',
                'empty_data' => '0',
            ])

            ->add('operatorPowerE', TextType::class, [
                'label' => 'Power E',
                'empty_data' => '0',
            ])
            ->add('operatorPowerHighA', TextType::class, [
                'label' => 'PowerHigh A (>200W)',
                'empty_data' => '0',
            ])
            ->add('operatorPowerHighB', TextType::class, [
                'label'         => 'PowerHigh B (>200W)',
                'empty_data'    => '0',
            ])
            ->add('dimensionHeight', TextType::class, [
                'label'         => 'Dimension height',
                'empty_data'    => '0',
            ])
            ->add('dimensionWidth', TextType::class, [
                'label'         => 'Dimension width',
                'empty_data'    => '0',
            ])
            ->add('isBifacial', SwitchType::class, [
                'label' => 'is a Bifacial Modul',
                'help' => '[isBifacial]',
            ])
            ->add('annotation', CKEditorType::class, [
                'label' => 'an Annotation',
                'empty_data' => '',
                'config' => ['toolbar' => 'my_toolbar'],
            ])

            ######### Voltage ##########dataSheet_1

            ->add('operatorVoltageA', TextType::class, [
                'label'         => 'Voltage A',
                'empty_data'    => '0',
            ])
            ->add('operatorVoltageB', TextType::class, [
                'label'         => 'Voltage B',
                'empty_data'    => '0',
            ])

            ->add('operatorVoltageHightA', TextType::class, [
                'label'         => 'VoltageHight A (>200W)',
                'empty_data'    => '0',
            ])
            ->add('operatorVoltageHightB', TextType::class, [
                'label'         => 'VoltageHight B (>200W)',
                'empty_data'    => '0',
            ])
            ->add('operatorVoltageHightC', TextType::class, [
                'label'         => 'VoltageHight C (>200W)',
                'empty_data'    => '0',
            ])
            ->add('backSideFactor', TextType::class, [
                'label'         => 'Factor to extend Irr on BFIM [%]',
                'empty_data'    => '0'
            ])

            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################

            ->add('close', SubmitType::class, [
                'label' => 'Close without save',
                'attr' => ['class' => 'secondary small', 'formnovalidate' => 'formnovalidate'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Databases',
                'attr' => ['class' => 'primary small', 'formnovalidate' => 'formnovalidate'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close Databases',
                'attr' => ['class' => 'secondary small', 'formnovalidate' => 'formnovalidate'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageModulesDB::class,
        ]);
    }
}

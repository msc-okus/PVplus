<?php


namespace App\Form\Anlage;


use App\Entity\AnlageModules;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModulesListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type',TextType::class, [
                'label'     => 'Module Type'
            ])
            ->add('power',TextType::class, [
                'empty_data'    => '0',
            ])
            ->add('tempCoefCurrent',TextType::class, [
                'label'         => 'Temp. Coef. Current',
                'empty_data'    => '0',
            ])
            ->add('tempCoefPower',TextType::class, [
                'label'         => 'Temp. Coef. Power',
                'empty_data'    => '0',
            ])
            ->add('degradation',TextType::class, [
                'label'         => 'Module degradation',
                'empty_data'    => '0',
            ])
            ->add('operatorCurrentA',TextType::class, [
                'label'         => 'A',
                'empty_data'    => '',
            ])
            ->add('operatorCurrentB',TextType::class, [
                'label'         => 'B',
                'empty_data'    => '',
            ])
            ->add('operatorCurrentC',TextType::class, [
                'label'         => 'C',
                'empty_data'    => '',
            ])
            ->add('operatorCurrentD',TextType::class, [
                'label'         => 'D',
                'empty_data'    => '',
            ])
            ->add('operatorCurrentE',TextType::class, [
                'label'         => 'E',
                'empty_data'    => '',
            ])
            ->add('operatorPowerA',TextType::class, [
                'label'         => 'A',
                'empty_data'    => '',
            ])
            ->add('operatorPowerB',TextType::class, [
                'label'         => 'B',
                'empty_data'    => '',
            ])
            ->add('operatorPowerC',TextType::class, [
                'label'         => 'C',
                'empty_data'    => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageModules::class,
        ]);
    }
}
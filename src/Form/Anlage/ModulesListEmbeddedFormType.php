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
            ->add('irrDiscount1',TextType::class, [
                'label'         => 'Irr Discount 1-50',
                'empty_data'    => '0',
            ])
            ->add('irrDiscount2',TextType::class, [
                'label'         => 'Irr Discount 51-100',
                'empty_data'    => '0',
            ])
            ->add('irrDiscount3',TextType::class, [
                'label'         => 'Irr Discount 101-150',
                'empty_data'    => '0',
            ])
            ->add('irrDiscount4',TextType::class, [
                'label'         => 'Irr Discount 151-200',
                'empty_data'    => '0',
            ])
            ->add('irrDiscount5',TextType::class, [
                'label'         => 'Irr Discount 201-250',
                'empty_data'    => '0',
            ])
            ->add('irrDiscount6',TextType::class, [
                'label'         => 'Irr Discount 251-300',
                'empty_data'    => '0',
            ])
            ->add('irrDiscount7',TextType::class, [
                'label'         => 'Irr Discount 301-350',
                'empty_data'    => '0',
            ])
            ->add('irrDiscount8',TextType::class, [
                'label'         => 'Irr Discount 351-400',
                'empty_data'    => '0',
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
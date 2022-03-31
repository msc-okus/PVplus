<?php

namespace App\Form\Anlage;
use App\Entity\AnlageModules;
use App\Entity\EconomicVarValues;
use App\Form\Type\CustomNumber;
use App\Form\Type\SwitchType;
use Doctrine\DBAL\Types\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EconomicVarsValuesEmbeddedFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('month', ChoiceType::class,[
                'label'         => 'month',
                'required'      => true,
                'choices'       =>[
                    'January'   => 1,
                    'February'  => 2,
                    'March'     => 3,
                    'April'     => 4,
                    'May'       => 5,
                    'June'      => 6,
                    'July'      => 7,
                    'August'    => 8,
                    'September' => 9,
                    'October'   => 10,
                    'November'  => 11,
                    'December'  => 12,
                ]
            ])
            ->add('year', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class,[
                'label'         => 'year',
                'required'      => true,
            ])
            ->add('KwHPrice', CustomNumber::class, [
                'label'         => 'Kw/h Price:'
            ])
            ->add('var_1', CustomNumber::class, [
                'label'         => 'Variable 1',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_1s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_2', CustomNumber::class, [
                'label'         => 'Variable 2',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_2s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_3', CustomNumber::class, [
                'label'         => 'Variable 3',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_3s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_4', CustomNumber::class, [
                'label'         => 'Variable 4',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_4s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_5', CustomNumber::class, [
                'label'         => 'Variable 5',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_5s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_6', CustomNumber::class, [
                'label'         => 'Variable 6',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_6s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_7', CustomNumber::class, [
                'label'         => 'Variable 7',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_7s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_8', CustomNumber::class, [
                'label'         => 'Variable 8',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_8s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_9', CustomNumber::class, [
                'label'         => 'Variable 9',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_9s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_10', CustomNumber::class, [
                'label'         => 'Variable 10',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_10s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_11', CustomNumber::class, [
                'label'         => 'Variable 11',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_11s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_12', CustomNumber::class, [
                'label'         => 'Variable 12',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_12s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_13', CustomNumber::class, [
                'label'         => 'Variable 13',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_13s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_14', CustomNumber::class, [
                'label'         => 'Variable 14',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_14s', SwitchType::class,[
                'mapped'        => false,
            ])
            ->add('var_15', CustomNumber::class, [
                'label'         => 'Variable 15',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('var_15s', SwitchType::class,[
                'mapped'        => false,
            ]);


    }
        public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EconomicVarValues::class,
        ]);
    }



}
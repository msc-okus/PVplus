<?php

namespace App\Form\Anlage;
use App\Entity\AnlageModules;
use App\Entity\EconomicVarValues;
use Doctrine\DBAL\Types\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EconomicVarsValuesEmbeddedFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('month', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class,[
                'label'         => 'month',
                'required'      => true,
            ])
            ->add('year', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class,[
                'label'         => 'year',
                'required'      => true,
            ])
            ->add('var_1', TextType::class, [
                'label'         => 'Variable 1',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_2', TextType::class, [
                'label'         => 'Variable 2',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_3', TextType::class, [
                'label'         => 'Variable 3',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_4', TextType::class, [
                'label'         => 'Variable 4',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_5', TextType::class, [
                'label'         => 'Variable 5',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_6', TextType::class, [
                'label'         => 'Variable 6',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_7', TextType::class, [
                'label'         => 'Variable 7',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_8', TextType::class, [
                'label'         => 'Variable 8',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_9', TextType::class, [
                'label'         => 'Variable 9',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_10', TextType::class, [
                'label'         => 'Variable 10',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_11', TextType::class, [
                'label'         => 'Variable 11',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_12', TextType::class, [
                'label'         => 'Variable 12',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_13', TextType::class, [
                'label'         => 'Variable 13',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_14', TextType::class, [
                'label'         => 'Variable 14',
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('var_15', TextType::class, [
                'label'         => 'Variable 15',
                'empty_data'    => '',
                'required'      => false,
            ]);


    }
        public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EconomicVarValues::class,
        ]);
    }



}
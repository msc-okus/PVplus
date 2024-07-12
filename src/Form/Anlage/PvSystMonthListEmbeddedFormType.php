<?php

namespace App\Form\Anlage;

use App\Entity\AnlagenPvSystMonth;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PvSystMonthListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('month', ChoiceType::class, [
                'choices' => array_combine(range(1, 12), range(1, 12)),
                'placeholder' => 'please choose',
            ])
            ->add('prDesign', TextType::class, [
                'label' => 'PR Design',
                'empty_data' => 0,
                'required' => false,
            ])
            ->add('ertragDesign', TextType::class, [
                'label' => 'eGrid Design',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('irrDesign', TextType::class, [
                'label' => 'Irradiation',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('tempAmbientDesign', TextType::class, [
                'label' => 'Temp Ambient',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('tempArrayAvgDesign', TextType::class, [
                'label' => 'Temp Array AVG',
                'empty_data' => '',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlagenPvSystMonth::class,
            'required' => false,
        ]);
    }
}

<?php

namespace App\Form\Anlage;

use App\Entity\AnlageLegendReport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonthlyLegendListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', HiddenType::class, [
                'data' => 'monthly',
            ])
            ->add('row', TextType::class, [
                'label' => 'Row',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('title', TextType::class, [
                'label' => 'Title / Formula',
                'empty_data' => '',
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unit',
                'empty_data' => '',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'empty_data' => '',
            ])
            ->add('source', TextType::class, [
                'label' => 'Description',
                'empty_data' => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageLegendReport::class,
            'required' => false,
        ]);
    }
}

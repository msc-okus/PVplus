<?php

namespace App\Form\ChartControl;

use App\Form\Model\ChartControlModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChartControlFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('selectedChart')
            ->add('selectedGroup')
            ->add('selectedSet')
            ->add('selectedInverter')
            ->add('to', DateType::class, [
                'empty_data' => new \DateTime('now'),
            ])
            ->add('optionDate')
            ->add('optionStep')
            ->add('fromdate')

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChartControlModel::class,
            'required' => false,
        ]);
    }
}

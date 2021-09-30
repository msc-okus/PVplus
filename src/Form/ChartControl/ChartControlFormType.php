<?php

namespace App\Form\ChartControl;

use App\Form\Model\ChartControlModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Date;

class ChartControlFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('selectedChart')
            ->add('selectedGroup')
            ->add('selectedInverter')
            ->add('to', DateType::class, [
                'empty_data' => new \DateTime('now'),
            ])
            ->add('optionDate')

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ChartControlModel::class,
        ]);
    }
}


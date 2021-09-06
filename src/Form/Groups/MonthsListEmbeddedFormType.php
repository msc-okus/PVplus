<?php

namespace App\Form\Groups;

use App\Entity\AnlageGroupMonths;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonthsListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $monthArray = [
            'January'   => '1',
            'February'  => '2',
            'March'     => '3',
            'April'     => '4',
            'May'       => '5',
            'June'      => '6',
            'July'      => '7',
            'August'    => '8',
            'September' => '9',
            'October'   => '10',
            'November'  => '11',
            'December'  => '12',
        ];

        $builder

            ->add('month', ChoiceType::class, [
                'choices'       => $monthArray,
                'placeholder'   => 'Please Choose',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('irrUpper', TextType::class, [
                'required'  => false,
                ])
            ->add('irrLower', TextType::class, [
                'required'  => false,
            ])
            ->add('shadowLoss', TextType::class, [
                'required'  => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageGroupMonths::class,
        ]);
    }
}
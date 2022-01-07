<?php

namespace App\Form;

use App\Entity\AnlageCase6;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Case6FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stampFrom')
            ->add('stampTo')
            ->add('inverter')
            ->add('reason')
            ->add('anlage')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageCase6::class,
        ]);
    }
}

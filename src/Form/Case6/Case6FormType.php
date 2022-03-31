<?php

namespace App\Form\Case6;

use App\Entity\AnlageCase6;
use App\Form\Type\DateString;
use Doctrine\DBAL\Types\TextType;
use koolreport\excel\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Case6FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stampFrom',DateString::class,[
        'widget' => 'single_text',
    ])
            ->add('stampTo', DateString::class,[
                'widget' => 'single_text',
            ])
            ->add('inverter')
            ->add('reason', \Symfony\Component\Form\Extension\Core\Type\TextType::class,[
                'empty_data' => ' ',
                'required' => false
            ])
            ->add('anlage')
            ->add('save', SubmitType::class, [
                'label'     => 'Save',
                'attr'      => ['class' => 'primary save'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageCase6::class,
        ]);
    }
}

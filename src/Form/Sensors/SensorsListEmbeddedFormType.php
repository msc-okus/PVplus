<?php

namespace App\Form\Sensors;

use App\Entity\AnlageSensors;
use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SensorsListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nameShort', TextType::class, [

            ])
            ->add('name', TextType::class, [

            ])
            ->add('type', TextType::class, [

            ])
            ->add('useToCalc', SwitchType::class, [
                'required' => false,
            ])
            ->add('orientation', TextType::class, [
                'required' => false,
            ])
            ->add('vcomId', TextType::class, [
                'required' => false,
            ])
            ->add('vcomAbbr', TextType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageSensors::class,
        ]);
    }
}

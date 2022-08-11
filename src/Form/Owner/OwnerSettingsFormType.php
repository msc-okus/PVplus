<?php

namespace App\Form\Owner;

use App\Entity\OwnerSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnerSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('nameDep1', TextType::class, [

            ])
            ->add('nameDep2', TextType::class, [

            ])
            ->add('nameDep3', TextType::class, [

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OwnerSettings::class,
        ]);
    }
}
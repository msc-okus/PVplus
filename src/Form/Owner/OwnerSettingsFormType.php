<?php

namespace App\Form\Owner;

use App\Entity\OwnerSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnerSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameDep1', TextType::class, [

            ])
            ->add('nameDep2', TextType::class, [

            ])
            ->add('nameDep3', TextType::class, [

            ])
            ->add('mcUser', TextType::class, [

            ])
            ->add('mcPassword', TextType::class, [

            ])
            ->add('mcToken', TextType::class, [

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OwnerSettings::class,
        ]);
    }
}
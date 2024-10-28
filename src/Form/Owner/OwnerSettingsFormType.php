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
                'label' => 'Name Department 1',
                'help'  => '[nameDep1]<br>Default Value is "O&M"'
            ])
            ->add('nameDep2', TextType::class, [
                'label' => 'Name Department 2',
                'help'  => '[nameDep2]<br>Default Value is "EPC"'
            ])
            ->add('nameDep3', TextType::class, [
                'label' => 'Name Department 3',
                'help'  => '[nameDep3]<br>Default Value is "AM (Aset Management)"'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OwnerSettings::class,
            'required' => false,
        ]);
    }
}
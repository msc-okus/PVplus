<?php

namespace App\Form\Owner;

use App\Entity\OwnerFeatures;
use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnerFeaturesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('aktDep1', SwitchType::class, [
                'label'     => 'aktiviere Departemnet 1 (O&M)',
            ])
            ->add('aktDep2', SwitchType::class, [
                'label'     => 'aktiviere Departemnet 2 (EPC)',
            ])
            ->add('aktDep3', SwitchType::class, [
                'label'     => 'aktiviere Departemnet 3 (AM)',
            ])
            ->add('SplitInverter', SwitchType::class, [
                'label'     => 'Split by Inverter',
            ])
            ->add('SplitGap', SwitchType::class, [
                'label'     => 'Split by Time',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OwnerFeatures::class,
        ]);
    }
}
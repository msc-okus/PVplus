<?php

namespace App\Form\Anlage;

use App\Entity\AnlagenMonthlyData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class MonthlyYieldListEmbeddedFormType extends AbstractType
{

    public function __construct(private Security $security)
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDeveloper    = $this->security->isGranted('ROLE_DEV');
        $isG4N          = $this->security->isGranted('ROLE_G4N');

        $builder
            ->add('year', ChoiceType::class, [
                'choices'       => [2019 => 2019, 2020 => 2020, 2021 => 2021, 2022 => 2022],
                'placeholder'   => 'please choose',
            ])
            ->add('month', ChoiceType::class, [
                'choices'       => array_combine(range(1, 12), range(1, 12)),
                'placeholder'   => 'please choose',
            ])
            ->add('pvSystErtrag', TextType::class, [
                'label'         => 'Yield [kWh]',
                'empty_data'    => 0,
                'required'      => false,
            ])
            ->add('pvSystPR', TextType::class, [
                'label'         => 'PR [%]',
                'empty_data'    => 0,
                'required'      => false,
            ])
            ->add('externMeterDataMonth', TextType::class, [
                'label'         => 'external Meter Data',
                'empty_data'    => 0,
                'required'      => false,
            ])
            ->add('pvSystIrr', TextType::class, [
                'label'         => 'PvSyst Irradiation',
                'empty_data'    => 0,
                'required'  => false,
            ])
        ;
        if ($isG4N) {
            $builder
                ->add('irrCorrectedValuMonth', TextType::class, [
                    'label'         => 'manual corrected Irr',
                    'empty_data'    => 0,
                    'required'      => false,
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlagenMonthlyData::class,
        ]);
    }
}

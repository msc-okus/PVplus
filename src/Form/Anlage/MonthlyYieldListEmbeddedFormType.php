<?php

namespace App\Form\Anlage;

use App\Entity\AnlagenMonthlyData;
use App\Helper\PVPNameArraysTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class MonthlyYieldListEmbeddedFormType extends AbstractType
{

    use PVPNameArraysTrait;

    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDeveloper    = $this->security->isGranted('ROLE_DEV');
        $isG4N          = $this->security->isGranted('ROLE_G4N');



        $builder
            ->add('year', ChoiceType::class, [
                'choices'       => self::yearsArray(),
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
            ->add('tModAvg', TextType::class, [
                'label'         => 'T_mod_avg',
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

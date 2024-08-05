<?php

namespace App\Form\Anlage;

use App\Entity\AnlagenMonthlyData;
use App\Helper\PVPNameArraysTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonthlyYieldListEmbeddedFormType extends AbstractType
{

    use PVPNameArraysTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                'required'      => true,
            ])
            ->add('pvSystErtrag', TextType::class, [
                'label'         => 'Yield [kWh]',
                'empty_data'    => 0,
            ])
            ->add('pvSystPR', TextType::class, [
                'label'         => 'PR [%]',
                'empty_data'    => 0,
            ])
            ->add('externMeterDataMonth', TextType::class, [
                'label'         => 'external Meter Data',
                'empty_data'    => 0,
            ])
            ->add('pvSystIrr', TextType::class, [
                'label'         => 'PvSyst Irradiation',
                'empty_data'    => 0,
            ])
            ->add('tModAvg', TextType::class, [
                'label'         => 'T_mod_avg',
                'empty_data'    => 0,
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlagenMonthlyData::class,
            'required' => false,
        ]);
    }
}

<?php

namespace App\Form\Ticket;

use App\Entity\TicketDate;
use App\Helper\PVPNameArraysTrait;
use FluidTYPO3\Flux\Form\Field\DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class TicketDateEmbeddedFormType extends AbstractType
{
    use PVPNameArraysTrait;

    public function __construct(
        private Security $security,
        private TranslatorInterface $translator,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin     = $this->security->isGranted('ROLE_ADMIN');

        $builder
            ->add('begin', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'readonly' => true,
                    'disabled' => true,
                ],
            ])
            ->add('end', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'readonly' => true,
                    'disabled' => true,
                ],
            ])

            ########### PA Tickets ###########
            ->add('errorType', ChoiceType::class, [
                'label'         => 'Type of error',
                'choices'       => self::errorType(),
                'placeholder'   => 'Please select …',
                'disabled'      => false,
                'empty_data'    => '',
            ])
            ->add('dataGapEvaluation', ChoiceType::class, [
                'required'  => false,
                'placeholder'   => 'please Choose ...',
                'choices'       => [
                    'outage'        => 10,
                    'comm. issue'   => 20,
                ],
            ])
            ->add('kpiPaDep1',ChoiceType::class, [
                'label'         => 'O&M',
                'choices'       => self::kpiPaDep1(),
                'placeholder'   => 'Please select …',
                'empty_data'    => '',
            ])
            ->add('kpiPaDep2',ChoiceType::class, [
                'label'         => 'EPC',
                'choices'       => self::kpiPaDep2(),
                'placeholder'   => 'Please select …',
                'empty_data'    => '',
            ])
            ->add('kpiPaDep3',ChoiceType::class, [
                'label'         => 'AM',
                'choices'       => self::kpiPaDep3(),
                'placeholder'   => 'Please select …',
                'empty_data'    => '',
            ])
            ;

        ########### Performance Tickets ###########
        if ($isDeveloper) {
            $builder
                ->add('performanceKpi', ChoiceType::class, [
                    'label'     => 'Performance KPI',
                    'choices'   => self::kpiPerformace(),
                    'mapped'    => false
                ])
                ->add('performanceKpiBehaviorDep1', ChoiceType::class, [
                    'label'     => 'Behavior Dep 1',
                    'choices'   => [
                        'exclude'   => 'exclude',
                        'replace [kWh]'   => 'replace',
                        'correct [kWh]'   => 'correct'
                    ],
                    'mapped'    => false
                ])
                ->add('performanceKpiBehaviorDep2', ChoiceType::class, [
                    'label'     => 'Behavior Dep 1',
                    'choices'   => [
                        'exclude'   => 'exclude',
                        'replace [kWh]'   => 'replace',
                        'correct [kWh]'   => 'correct'
                    ],
                    'mapped'    => false
                ])
                ->add('performanceKpiBehaviorDep3', ChoiceType::class, [
                    'label'     => 'Behavior Dep 1',
                    'choices'   => [
                        'exclude'   => 'exclude',
                        'replace [kWh]'   => 'replace',
                        'correct [kWh]'   => 'correct'
                    ],
                    'mapped'    => false
                ])
                ->add('performanceKpiValueDep1', TextType::class, [
                    'label'     => 'Value',
                    'attr'      => [
                        'placeholder' => 'value [kWh]'
                    ],
                    'mapped'    => false
                ])
                ->add('performanceKpiValueDep2', TextType::class, [
                    'label'     => 'Value',
                    'attr'      => [
                        'placeholder' => 'value [kWh]'
                    ],
                    'mapped'    => false
                ])
                ->add('performanceKpiValueDep3', TextType::class, [
                    'label'     => 'Value',
                    'attr'      => [
                        'placeholder' => 'value [kWh]'
                    ],
                    'mapped'    => false
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TicketDate::class,
        ]);
    }
}

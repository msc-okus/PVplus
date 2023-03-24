<?php

namespace App\Form\Ticket;

use App\Entity\TicketDate;
use App\Form\Type\SwitchType;
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
        $isBeta      = $this->security->isGranted('ROLE_BETA');

        $builder
            ->add('begin', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'readonly' => true,
                    'data-ticket-edit-target' => 'formBeginDate'
                ],
            ])
            ->add('end', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'readonly' => true,
                    'data-ticket-edit-target' => 'formEndDate'
                ],
            ])
            ->add('beginHidden', DateTimeType::class, [
                'widget' => 'single_text',
                'mapped' => false,
                'attr' => [
                    'readonly' => true,
                    'data-ticket-edit-target' => 'formBeginHidden',
                    'hidden' => true,
                    'class' => 'is-hidden'
                ],
            ])
            ->add('endHidden', DateTimeType::class, [
                'widget' => 'single_text',
                'mapped' => false,
                'attr' => [
                    'readonly' => true,
                    'data-ticket-edit-target' => 'formEndHidden',
                    'hidden' => true,
                    'class' => 'is-hidden'
                ],
            ])
            ->add('reasonChoose',ChoiceType::class, [
                'mapped' => false,
                'choices'       => [],
                'placeholder'   => 'Please select …',
                'empty_data'    => '',
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
        if ($isDeveloper || $isBeta) {
            $builder
                ########## exclude Sensors &  replace Sensor

                // at the moment only Dummy - no field
                ->add('sensors', ChoiceType::class, [
                    'label'     => 'excludeSensors',
                    'choices'   => ['Wind' => 1, 'Irr' => 2, 'ModulTemp' => 3, 'and so on' => 4],
                    'placeholder' => 'please chose',
                    'mapped' => false
                ])

                // new field
                ########### replace Energy (Irr)
                ->add('valueEnergy', TextType::class, [
                    'label'     => 'Value energy',
                    'attr'      => [
                        'placeholder' => 'value [kWh]'
                    ],
                ])
                ########### exclude from PR/Energy & replace Energy (Irr)
                // new field (bool)
                ->add('useHour', SwitchType::class, [
                    'label'     => 'use hour (PVsyst)',
                    'attr'      => [
                        'data-action' => 'change->ticket-edit#hourCheck',
                        'data-ticket-edit-target' => 'formHour'
                    ]
                ])

                ########### replace Energy (Irr)
                // new field (bool)
                ->add('replaceEnergy', SwitchType::class, [
                    'label'     => 'replace Energy with PVsyst',
                    'attr' => [
                        'data-action' => 'change->ticket-edit#replaceCheck',
                        'data-ticket-edit-target' => 'formReplace'
                    ],
                    // new field (bool)
                ])->add('replaceIrr', SwitchType::class, [
                    'label'     => 'replace Irradiation with PVsyst',
                    'attr' => [
                        'data-action' => 'change->ticket-edit#replaceCheck',
                        'data-ticket-edit-target' => 'formReplaceIrr'
                    ],
                ])
                // new field
                ->add('valueIrr', TextType::class, [
                    'label'     => 'Value Irradiation',
                    'attr'      => [
                        'placeholder' => 'value [W/qm]'
                    ],
                ])

                ########### correct Energy
                // new field
                ->add('correctEnergyValue', TextType::class, [
                    'label'     => 'correct Energy Value',
                    'attr'      => [
                        'placeholder' => 'value [kWh]'
                    ],
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

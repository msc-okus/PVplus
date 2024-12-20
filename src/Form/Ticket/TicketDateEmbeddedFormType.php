<?php

namespace App\Form\Ticket;

use App\Entity\TicketDate;
use App\Form\Type\SwitchType;
use App\Helper\PVPNameArraysTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class TicketDateEmbeddedFormType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator
    )
    {
    }

    use PVPNameArraysTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin     = $this->security->isGranted('ROLE_ADMIN');
        $isBeta      = $this->security->isGranted('ROLE_BETA');
        $isTicket      = $this->security->isGranted('ROLE_TICKET');

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
                    //'data-ticket-edit-target' => 'formEndDate'
                ],
            ])
            ->add('beginHidden', TextType::class, [
                'data' => '',
                //'widget' => 'single_text',
                'attr' => [
                    'readonly' => true,
                    'data-ticket-edit-target' => 'formBeginHidden',
                    'hidden' => true,
                    'class' => 'is-hidden'
                ],
            ])
            ->add('endHidden', TextType::class, [
                'data' => '',
                //'widget' => 'single_text',
                'attr' => [
                    'readonly' => true,
                    'data-ticket-edit-target' => 'formEndHidden',
                    'hidden' => true,
                    'class' => 'is-hidden'
                ],
            ])

            ->add('reasonText',TextType::class, [
                'empty_data' => '',
                'attr'          => [
                    'data-ticket-edit-target' => 'formReasonSelect'
                ]

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
        if ($isDeveloper || $isBeta || $isTicket) {
            $builder

                ########## exclude Sensors &  replace Sensor
                ->add('PRExcludeMethod', ChoiceType::class, [
                    'choices' => self::PRExcludeMethods(),
                ])
                // at the moment only Dummy - no field
                ->add('sensors', TextType::class, [
                    'label'     => 'excludeSensors',
                    'attr' => [
                        'readonly' => true,
                    ],

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
                        'data-action' => 'change->ticket-edit#checkCategory',
                        'data-ticket-edit-target' => 'formReplace'
                    ],
                ])
                ->add('replaceEnergyG4N', SwitchType::class, [
                    'label'     => 'replace Energy with G4N Expected',
                    'attr' => [
                        'data-action' => 'change->ticket-edit#checkCategory',
                        'data-ticket-edit-target' => 'formReplaceG4N'
                    ],
                    // new field (bool)
                ])
                ->add('replaceIrr', SwitchType::class, [
                    'label'     => 'replace Irradiation with PVsyst',
                    'attr' => [
                        'data-action' => 'change->ticket-edit#checkCategory',
                        'data-ticket-edit-target' => 'formReplaceIrr'
                    ],
                ])
                // new field
                ->add('valueIrr', TextType::class, [
                    'label'     => 'Value Irradiation',
                    'attr'      => [
                        'placeholder' => 'value [kWh/m²]'
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

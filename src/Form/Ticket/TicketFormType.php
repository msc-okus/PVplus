<?php

namespace App\Form\Ticket;

use App\Entity\Anlage;
use App\Entity\Ticket;
use App\Form\Type\AnlageTextType;
use App\Form\Type\SwitchType;
use App\Helper\PVPNameArraysTrait;
use App\Repository\AnlagenRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Stakovicz\UXCollection\Form\UXCollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class TicketFormType extends AbstractType
{
    use PVPNameArraysTrait;

    private AnlagenRepository $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository, TranslatorInterface $translator)
    {
        $this->anlagenRepository = $anlagenRepository;
        $this->translator = $translator;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Ticket $ticket */
        $ticket = $options['data'] ?? null;
        $isNewTicket = (bool) $ticket;

        if (!$isNewTicket) {
            $builder
                ->add('anlage', AnlageTextType::class, [
                    'label' => 'Plant name ',
                    'attr' => [
                        'readonly' => true,
                    ],
                ])
                ->add('begin', DateTimeType::class, [
                    'label' => 'Begin',
                    'label_html' => true,
                    'required' => false,
                    'input' => 'datetime',
                    'widget' => 'single_text',
                    'data' => new \DateTime(date('Y-m-d H:i', time() - time() % 900)),
                    'attr' => ['step' => 900, 'data-action' => 'change->ticket-list#check', 'data-ticket-list-target' => 'formBegin'],
                ])
                ->add('end', DateTimeType::class, [
                    'label' => 'End',
                    'label_html' => true,
                    'required' => true,
                    'input' => 'datetime',
                    'widget' => 'single_text',
                    'data' => new \DateTime(date('Y-m-d H:i', 900 + time() - time() % 900)),
                    'attr' => ['step' => 900, 'data-action' => 'change->ticket-list#check', 'data-ticket-list-target' => 'formEnd'],
                ])
                ->add('inverter', TextType::class, [
                    'label' => 'Inverter',
                    'required' => true,
                    'data' => '*',
                    'help' => '* = all Invertres, 1-3 = Inverter 1 to 3, ...',
                ])
            ;
        } else {
            $builder
                ->add('anlage', AnlageTextType::class, [
                    'label' => 'Plant name ',
                    'attr' => [
                        'readonly' => true,
                    ],
                ])
                ->add('begin', DateTimeType::class, [
                    'label' => 'Begin',
                    'label_html' => true,
                    'required' => false,
                    'attr' => [
                        'max' => $ticket->getBegin()->format("Y-m-d\TH:i"),
                        'step' => '600',
                    ],
                    'widget' => 'single_text',
                ])
                ->add('end', DateTimeType::class, [
                    'label' => 'End',
                    'label_html' => true,
                    'required' => true,
                    'widget' => 'single_text',
                    'attr' => ['min' => $ticket->getEnd()->format("Y-m-d\TH:i")],
                ])
            ;
        }
        $builder
            ->add('dates', UXCollectionType::class, [
                'required' => false,
                'entry_type' => TicketDateEmbeddedFormType::class,
            ])
            ->add('dataGapEvaluation', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'please Choose …',
                'choices' => [
                    'outage' => 'outage',
                    'comm. issue' => 'comm. issue',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => self::ticketStati(),
                'required' => true,
                'empty_data' => 30, // Work in Progress
                'invalid_message' => 'Please select a Status.',
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => self::ticketPriority(),
                'required' => true,
                'empty_data' => 10, // Low
                'invalid_message' => 'Please select a Priority.',
            ])
            ->add('alertType', ChoiceType::class, [
                'label' => 'Category of error ',
                'help' => 'data gap, inverter, ...',
                'choices' => self::errorCategorie(),
                'disabled' => $isNewTicket,
                'placeholder' => 'Please select ...',
                'invalid_message' => 'Please select a Error Category.',
                'empty_data' => 0,
            ])
            ->add('errorType', ChoiceType::class, [
                'label' => 'Type of error',
                'help' => 'OMC: Out of Management Control<br>EFOR: Equivalent Forced Outage Rate<br>SOR: Scheduled Uutage Rate',
                'choices' => self::errorType(),
                'placeholder' => 'Please select ...',
                'disabled' => false,
                'empty_data' => '',
                'required' => false,
            ])

            // ### List of Ticket Dates
            ->add('dates', UXCollectionType::class, [
                'entry_type' => TicketDateEmbeddedFormType::class,
            ])

            // ### ACTIONS
            ->add('dataGapEvaluation', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'please Choose ...',
                'choices' => [
                    'outage' => 'outage',
                    'comm. issue' => 'comm. issue',
                ],
            ])

            // ### Free Text for descriptions
            ->add('freeText', CKEditorType::class, [
                'config' => ['toolbar' => 'my_toolbar'],
                'attr' => ['rows' => '9'],
                'required' => false,
            ])
            ->add('answer', CKEditorType::class, [
                'config' => ['toolbar' => 'my_toolbar'],
                'attr' => ['rows' => '9'],
                'required' => false,
            ])

            // ###
            ->add('PR0', SwitchType::class, [
                'label' => 'PR',
                'required' => false,
            ])
            ->add('PA0C5', SwitchType::class, [
                'label' => 'PA5',
                'required' => false,
            ])
            ->add('PA0C6', SwitchType::class, [
                'label' => 'PA6',
                'required' => false,
            ])
            ->add('Yield0', SwitchType::class, [
                'label' => 'Yield',
                'required' => false,
            ])
            ->add('PR1', SwitchType::class, [
                'label' => 'PR',
                'required' => false,
            ])
            ->add('PA1C5', SwitchType::class, [
                'label' => 'PA5',
                'required' => false,
            ])
            ->add('PA1C6', SwitchType::class, [
                'label' => 'PA6',
                'required' => false,
            ])
            ->add('Yield1', SwitchType::class, [
                'label' => 'Yield',
                'required' => false,
            ])

            ->add('PR2', SwitchType::class, [
                'label' => 'PR',
                'required' => false,
            ])
            ->add('PA2C5', SwitchType::class, [
                'label' => 'PA5',
                'required' => false,
            ])
            ->add('PA2C6', SwitchType::class, [
                'label' => 'PA6',
                'required' => false,
            ])
            ->add('Yield2', SwitchType::class, [
                'label' => 'Yield',
                'required' => false,
            ]);
    }
}

<?php

namespace App\Form\Ticket;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Entity\Ticket;
use App\Form\Type\AnlageTextType;
use App\Form\Type\SwitchType;
use App\Helper\PVPNameArraysTrait;
use App\Repository\AnlagenRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Stakovicz\UXCollection\Form\UXCollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class TicketFormType extends AbstractType
{
    use PVPNameArraysTrait;

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly TranslatorInterface $translator,
        private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $isBeta = $this->security->isGranted('ROLE_BETA');
        $isG4N = $this->security->isGranted('ROLE_G4N');

        /** @var Ticket $ticket */
        $ticket = $options['data'] ?? null;

        $isNewTicket = ($ticket !== null && $ticket->getCreatedAt() !== null) ? false : true;
        $anlage = ($ticket != null) ? $ticket->getAnlage() : null;
        $full = ($anlage) ?  $anlage->getKpiTicket() : true;
        $errorCategorie = self::errorCategorie();

        $builder
            ->add('TicketName', TextType::class, [
                'label' => 'Ticket Identification',
                'help' => 'This tag helps the user distinguish between tickets',
            ])
            ->add('anlage', AnlageTextType::class, [
                'label' => 'Plant name ',
                'attr' => [
                    'readonly' => true,
                ],
            ]);
        if ($isNewTicket) {
            $builder->add('alertType', ChoiceType::class, [
                'label' => 'Category of ticket ',
                'help' => 'data gap, inverter, ...',
                'choices' => $errorCategorie,
                'placeholder' => 'Please select ...',
                'invalid_message' => 'Please select a Error Category.',
                'empty_data' => 0,
                'attr' => [
                    'data-action' => 'change->ticket-edit#saveCheck',
                    'data-ticket-edit-target' => 'formCategory',
                    'data-ticket-edit-edited-param' => 'false',
                ],
            ])
            ;
        } else {
            $builder->add('alertType', ChoiceType::class, [
                'label' => 'Category of ticket ',
                'help' => 'data gap, inverter, ...',
                'choices' => $errorCategorie,
                'disabled' => true,
                'placeholder' => 'Please select ...',
                'invalid_message' => 'Please select a Error Category.',
                'empty_data' => 0,
                'attr' => [
                    'data-action' => 'change->ticket-edit#saveCheck',
                    'data-ticket-edit-target' => 'formCategory',
                ],
            ]);
        }

        $builder
            ->add('inverter', TextType::class, [
                'label' => 'Inverter',
                'required' => true,
                'help' => '* = all Invertres',
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('begin', DateTimeType::class, [
                'label' => 'Begin',
                'label_html' => true,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'data-action' => 'blur->ticket-edit#beginCheck click->ticket-edit#setHiddenValue',
                    'data-ticket-edit-target' => 'formBegin',
                ],
            ])
            ->add('end', DateTimeType::class, [
                'label' => 'End',
                'label_html' => true,
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'data-action' => 'blur->ticket-edit#endCheck click->ticket-edit#setHiddenValue',
                    'data-ticket-edit-target' => 'formEnd'
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
            ->add('needsProofTAM', SwitchType::class, [
                'label' => 'proof by TAM',
            ])
            ->add('needsProofEPC', SwitchType::class, [
                'label' => 'proof by EPC',

            ])
            ->add('ProofAM', SwitchType::class, [
                'label' => 'proof by AM'
            ]);
            if($isAdmin || $isDeveloper || $isBeta){
                $builder ->add('needsProofg4n', SwitchType::class, [
                    'label' => 'proof by G4N'
                ]);
            }
            else {
                $builder ->add('needsProofg4n', SwitchType::class, [
                    'label' => 'proof by G4N'
                ]);
            }
            $builder->add('ignoreTicket', SwitchType::class, [
                'label' => 'Ignore',
            ])
            // ### Free Text for descriptions
            ->add('freeText', TextareaType::class, [
                #'config' => ['toolbar' => 'my_toolbar'],
                'attr' => ['rows' => '9'],
                'required' => false,
            ])
            ->add('answer', TextareaType::class, [
                #'config' => ['toolbar' => 'my_toolbar'],
                'attr' => ['rows' => '9'],
                'required' => false,
            ])

            // ### List of Ticket Dates
            ->add('dates', CollectionType::class, [
                'entry_type' => TicketDateEmbeddedFormType::class,
                'allow_add' => true, //This should do the trick.
            ])
            ->add('KpiStatus', ChoiceType::class, [
                'choices' => self::kpiStatus(),
                'placeholder' => 'please chose',
                'attr' => [
                    'data-ticket-edit-target' => 'formkpiStatus'
                ],

            ])
            ->add('Scope', ChoiceType::class, [
                'label' => 'Scope',
                'choices' => self::scope(),
                'expanded' => true,
                'multiple' => true
            ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}

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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class TicketFormType extends AbstractType
{
    use PVPNameArraysTrait;

    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private TranslatorInterface $translator,
        private Security $security)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin     = $this->security->isGranted('ROLE_ADMIN');

        /** @var Ticket $ticket */
        $ticket = $options['data'] ?? null;

        if ($ticket) {
            $isNewTicket = $ticket->getCreatedAt() !== null;
        } else {
            $isNewTicket = false;
        }
        dump($isNewTicket, $ticket->getCreatedAt());

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
            ])
            ->add('alertType', ChoiceType::class, [
                'label' => 'Category of ticket ',
                'help' => 'data gap, inverter, ...',
                'choices' => self::errorCategorie(),
                'disabled' => $isNewTicket,
                'placeholder' => 'Please select ...',
                'invalid_message' => 'Please select a Error Category.',
                'empty_data' => 0,
                'attr' => [
                    'data-action' => 'change->ticket-edit#saveCheck',
                    'data-ticket-edit-target' => 'formCategory'
                ],
            ])
            ->add('inverter', TextType::class, [
                'label' => 'Inverter',
                'required' => true,
                'help' => '* = all Invertres',
                'attr' => [
                    'readonly' => true,
                    'disabled' => true,
                ],
            ])
            ->add('begin', DateTimeType::class, [
                'label' => 'Begin',
                'label_html' => true,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'step' => 900,
                    'data-action' => 'change->ticket-edit#saveCheck',
                    'data-ticket-edit-target' => 'formBegin',
                    'max' => $isNewTicket ? $ticket->getBegin()->format("Y-m-d\TH:i") : ''
                ],
            ])
            ->add('end', DateTimeType::class, [
                'label' => 'End',
                'label_html' => true,
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'min' => $isNewTicket ? $ticket->getEnd()->format("Y-m-d\TH:i") : '',
                    'step' => 900,
                    'data-action' => 'change->ticket-edit#saveCheck',
                    'data-ticket-edit-target' => 'formEnd'],
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
            ->add('needsProof', SwitchType::class, [
                'label'         => 'Needs proof',
            ])
            ->add('ignoreTicket', SwitchType::class, [
                'label'         => 'Ignore',
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

            // ### List of Ticket Dates
            ->add('dates', CollectionType::class, [
                'entry_type' => TicketDateEmbeddedFormType::class,
                'allow_add' => true, //This should do the trick.
            ])

            // ### ACTIONS
            /*
            ->add('dataGapEvaluation', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'please Choose ...',
                'choices' => [
                    'outage' => 'outage',
                    'comm. issue' => 'comm. issue',
                ],
            ])
            */
            ;
    }


}

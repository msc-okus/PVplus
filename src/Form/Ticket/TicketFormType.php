<?php

namespace App\Form\Ticket;


use App\Entity\Ticket;
use App\Form\Type\AnlageTextType;
use App\Form\Type\SwitchType;
use App\Helper\PVPNameArraysTrait;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketFormType extends AbstractType
{
    use PVPNameArraysTrait;

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

        if ($ticket === null) {
            $builder
                ->add('anlage', AnlageTextType::class, [
                    'label' => 'Plant name ',
                    'attr' => [
                        'class' => 'js-autocomplete-anlagen input-group-field',
                        'data-autocomplete-url' => '/admin/anlagen/find'
                    ]
                ])
                ->add('begin', DateTimeType::class, [
                    'label' => 'Begin',
                    'label_html' => true,
                    'required' => false,
                    'input' => 'datetime',
                    'widget' => 'single_text',
                    'data' => new \DateTime("now")
                ]);
        } else {
            $builder
                ->add('anlage', AnlageTextType::class, [
                    'label' => 'Plant name ',
                    'attr' => [
                        'readonly' => true,
                    ]
                ])
                ->add('begin', DateTimeType::class, [
                    'label' => 'Begin',
                    'label_html' => true,
                    'required' => false,
                    'input' => 'datetime',
                    'widget' => 'single_text',
                ]);
        }
        $builder

            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => self::ticketStati(),
                'required' => true,
                'placeholder' => 'please Choose ...'
            ])
            ->add('end', DateTimeType::class, [
                'label' => 'End',
                'label_html' => true,
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => self::ticketPriority(),
                'required' => true,
                'placeholder' => 'please Choose ...'

            ])
            ->add('alertType', ChoiceType::class, [
                'label' => 'Category of error ',
                'help'  => 'data gap, inverter, ...',
                'choices' => self::errorCategorie(),
                'disabled' => true,
            ])
            ->add('errorType', ChoiceType::class, [
                'label' => 'Type of error',
                'help'  => 'SOR, EFOR, OMC',
                'choices' => self::errorType(),
                'disabled' => true,
            ])

            ->add('freeText', CKEditorType::class, [
                'config' => array('toolbar' => 'my_toolbar'),
                'required' => false,
            ])
            ->add('answer', CKEditorType::class, [
                'config' => array('toolbar' => 'my_toolbar'),
                'required' => false,
            ])
            ->add('PR0', SwitchType::class, [
                'label'     => 'PR',
                'required'  => false
            ])
            ->add('PA0C5', SwitchType::class,[
                'label'     => 'PA5',
                'required'  => false
            ])
            ->add('PA0C6', SwitchType::class,[
                'label'     => 'PA6',
                'required'  => false
            ])
            ->add('Yield0', SwitchType::class,[
                'label'     => 'Yield',
                'required'  => false
            ])
            ->add('PR1', SwitchType::class, [
                'label'     => 'PR',
                'required'  => false
            ])
            ->add('PA1C5', SwitchType::class,[
                'label'     => 'PA5',
                'required'  => false
            ])
            ->add('PA1C6', SwitchType::class,[
                'label'     => 'PA6',
                'required'  => false
            ])
            ->add('Yield1', SwitchType::class,[
                'label'     => 'Yield',
                'required'  => false
            ])

            ->add('PR2', SwitchType::class, [
                'label'     => 'PR',
                'required'  => false
            ])
            ->add('PA2C5', SwitchType::class,[
                'label'     => 'PA5',
                'required'  => false
            ])
            ->add('PA2C6', SwitchType::class,[
                'label'     => 'PA6',
                'required'  => false
            ])
            ->add('Yield2', SwitchType::class,[
                'label'     => 'Yield',
                'required'  => false
            ])

            ->add('save', SubmitType::class, [
                'label'     => 'Save',
                'attr'      => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label'     => 'Save and Close',
                'attr'      => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label'     => 'Close without save',
                'attr'      => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ]);

    }
}

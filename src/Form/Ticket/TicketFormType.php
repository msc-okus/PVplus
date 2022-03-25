<?php

namespace App\Form\Ticket;


use App\Entity\Ticket;
use App\Form\Type\AnlageTextType;
use App\Form\Type\SwitchType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketFormType extends AbstractType
{
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
                ]);
        }
        $builder

            ->add('status', ChoiceType::class, [
                'label' => 'Select the status',
                'choices' => [
                    'Open' => 10,
                    'Work in Progress' => 20,
                    'Closed' => 30
                ],
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

            ->add('freeText', CKEditorType::class, [
                'config' => array('toolbar' => 'my_toolbar'),
            ])
            ->add('description', TextType::class, [

            ])
            ->add('systemStatus', ChoiceType::class, [
                'label' => 'Select the status of the system',
                'choices' => [
                    'test' => 10,
                    'test2' => 20
                ],
                'required' => true,
                'placeholder' => 'Please Choose ...'
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Select the priority',
                'choices' => [
                    'Low' => 10,
                    'Normal' => 20,
                    'High' => 30,
                    'Urgent' => 40
                ],
                'required' => true,
                'placeholder' => 'please Choose ...'

            ])
            ->add('answer', CKEditorType::class, [
                'config' => array('toolbar' => 'my_toolbar'),
            ])
            ->add('PR0', SwitchType::class, [
                'label'     => 'PR',
                'required'  => false
            ])
            ->add('PA0C5', SwitchType::class,[
                'label'     => 'PA',
                'required'  => false
            ])
            ->add('PA0C6', SwitchType::class,[
                'label'     => 'PA',
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
                'label'     => 'PA',
                'required'  => false
            ])
            ->add('PA1C6', SwitchType::class,[
                'label'     => 'PA',
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
                'label'     => 'PA',
                'required'  => false
            ])
            ->add('PA2C6', SwitchType::class,[
                'label'     => 'PA',
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

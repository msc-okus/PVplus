<?php

namespace App\Form\Ticket;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Entity\Eigner;
use App\Entity\Ticket;
use App\Entity\User;
use App\Form\Type\AnlageTextType;
use App\Form\Type\SwitchType;
use ContainerRxXKGp9\getFosCkEditor_Form_TypeService;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
        $builder
            ->add('anlage', AnlageTextType::class,[

            ])

            ->add('status', ChoiceType::class,[
                'label'         => 'Select the status',
                'choices'       => [
                    'Open'              => 10,
                    'Work in Progress'  => 20,
                    'Closed'            => 30
                ],
                'required' => true,
                'placeholder'   => 'please Choose ...'
            ]);

        if ($ticket=== null)$builder

            ->add('begin', DateTimeType::class,[
                'label'         => 'Begin',
                'label_html'    => true,
                'required'      => false,
                'input'         =>'datetime',
                'widget'        =>'single_text',
                'data'          => new \DateTime("now")
                ]);
        else $builder
            ->add('begin', DateTimeType::class,[
                'label'         => 'Begin',
                'label_html'    => true,
                'required'      => false,
                'input'         =>'datetime',
                'widget'        =>'single_text',
            ]);

    $builder

        ->add('end', DateTimeType::class,[
            'label'         => 'End',
            'label_html'    => true,
            'required'      => true,
            'input'         =>'datetime',
            'widget'        =>'single_text',
        ])

        ->add('PR', SwitchType::class, [
            'label' => 'PR'
        ])
        ->add('PA', SwitchType::class,[
            'label' => 'PA'
        ])

        ->add('Yield', SwitchType::class,[
            'label' => 'Yield'
        ])

        ->add('freeText', CKEditorType::class ,[
            'config' => array('toolbar' => 'my_toolbar'),
        ])

        ->add('description', TextType::class,[

        ])

        ->add('systemStatus', ChoiceType::class,[
            'label'         => 'Select the status of the system',
            'choices'       => [
                'test' => 10,
                'test2' => 20
            ],
            'required' => true,
            'placeholder'   => 'Please Choose ...'
        ])

        ->add('priority', ChoiceType::class,[
            'label'         => 'Select the priority',
            'choices'       => [
                'Low'       => 10,
                'Normal'    => 20,
                'High'      => 30,
                'Urgent'    => 40
            ],
            'required' => true,
            'placeholder'   => 'please Choose ...'

        ])

        ->add('answer', CKEditorType::class ,[
            'config' => array('toolbar' => 'my_toolbar'),
        ])
        ->add('save', SubmitType::class, [
            'label' => 'Save',
            'attr' => ['class' => 'primary save'],
        ])

        ->add('saveclose', SubmitType::class, [
            'label' => 'Save and Close',
            'attr' => ['class' => 'primary saveclose'],
        ])

        ->add('close', SubmitType::class, [
            'label' => 'Close without save',
            'attr' => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
        ]);
    }

}

<?php

namespace App\Form;

use App\Entity\Ticket;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class TicketFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class,[
                'label'         => 'select the status',
                'choices'       => [
                // TO DECIDE
                ],
                'required' => true,
                'placeholder'   => 'please Choose ...'
            ])
            ->add('begin', DateType::class,[
                'label'         => 'Begin',
                'help'          => '[begin]',
                'label_html'    => true,
                'widget'        => 'single_text',
                'input'         => 'datetime',
                'empty_data'    => new \DateTime('now'),
                'required'      =>true,
            ])
            ->add('end', DateType::class,[
                'label'         => 'End',
                'help'          => '[end]',
                'label_html'    => true,
                'widget'        => 'single_text',
                'input'         => 'datetime',
                'empty_data'    => new \DateTime('now'),
            ])
            ->add('ticketActivity',DateType::class,[

            ])
            ->add('PR', Boolean::class,[

            ])
            ->add('PA', Boolean::class,[

            ])
            ->add('yield', Boolean::class,[

            ])
            ->add('freeText', TextType::class,[

            ])
            ->add('description', TextType::class,[

            ])
            ->add('systemStatus', ChoiceType::class,[
                'label'         => 'select the status of the system',
                'choices'       => [
                //TO DECIDE
                ],
                'required' => true,
                'placeholder'   => 'please Choose ...'
            ])
            ->add('priority', ChoiceType::class,[
                'label'         => 'select the priority',
                'choices'       => [
                // TO DECIDE
                ],
                'required' => true,
                'placeholder'   => 'please Choose any...'
            ])
            ->add('answer', TextType::class,[

            ])
            ->add('anlage', EntityType::class,[

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}

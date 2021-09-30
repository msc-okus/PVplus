<?php

namespace App\Form\Ticket;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
        $builder
            ->add('status', ChoiceType::class,[
                'label'         => 'select the status',
                'choices'       => [
                // TO DECIDE
                ],
                'required' => true,
                'placeholder'   => 'please Choose ...'
            ])
            ->add('begin', \Symfony\Component\Form\Extension\Core\Type\DateType::class,[
                'label'         => 'Begin',
                'label_html'    => true,

                'required'      =>true,
            ])
            ->add('end', \Symfony\Component\Form\Extension\Core\Type\DateType::class,[
                'label'         => 'End',
                'label_html'    => true,
                'required'      =>true,
            ])
            /*
            ->add('ticketActivity',\Symfony\Component\Form\Extension\Core\Type\DateType::class,[
                'label'         => 'Ticket Activity',
                'label_html'    => true,
                'required'      =>true,
            ])
*/


            ->add('PR', ChoiceType::class,[
                'label'         =>'PR',
                'expanded'      => true,
                'multiple'      => true,
            ])
          /*
            ->add('PA', Boolean::class,[

            ])
            ->add('yield', Boolean::class,[

            ])
            */
            ->add('freeText', TextareaType::class,[

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

            ])
            ->add('answer', TextareaType::class,[

            ])
            ->add('anlage', EntityType::class,[
                'class' => Anlage::class,
            ])

        ;
    }

}

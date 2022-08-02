<?php

namespace App\Form\Ticket;

use App\Entity\TicketDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketDateEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dataGapEvaluation', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'please Choose ...',
               # 'class' => 'no-margin',
                'choices' => [
                    'outage' => 'outage',
                    'comm. issue' => 'comm. issue'
                ]

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TicketDate::class,
        ]);
    }
}

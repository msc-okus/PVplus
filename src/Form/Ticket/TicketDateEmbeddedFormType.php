<?php

namespace App\Form\Ticket;

use App\Entity\TicketDate;
use App\Helper\PVPNameArraysTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketDateEmbeddedFormType extends AbstractType
{
    use PVPNameArraysTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('dataGapEvaluation', ChoiceType::class, [
                'required'  => false,
                'placeholder'   => 'please Choose ...',
                'choices'       => [
                    'outage'        => 'outage',
                    'comm. issue'   => 'comm. issue',
                ],
            ])
            ->add('errorType', ChoiceType::class, [
                'label'         => 'Type of error',
                'help'          => 'SOR, EFOR, OMC',
                'choices'       => self::errorType(),
                'placeholder'   => 'Please select …',
                'disabled'      => false,
                'empty_data'    => '',
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TicketDate::class,
        ]);
    }
}

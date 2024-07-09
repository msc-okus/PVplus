<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnlageStringAssigmentCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


            $builder
                ->add('anlage', ChoiceType::class, [
                    'choices' => $options['anlagen_choices'],
                    'placeholder' => 'Choose a plant',
                ])
                ->add('month', ChoiceType::class, [
                    'choices' => array_combine(range(1, 12), range(1, 12)),
                    'placeholder' => 'Choose a month',
                ])
                ->add('year', ChoiceType::class, [
                    'choices' => array_combine(range(2020, 2024), range(2020, 2024)),
                    'placeholder' => 'Choose a year',
                ])
                ->add('submit', SubmitType::class);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'anlagen_choices' => [],
            'required' => false,
        ]);
    }
}

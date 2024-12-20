<?php

namespace App\Form\Anlage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnlageStringAssigmentUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anlage', ChoiceType::class, [
                'choices' => $options['anlagen_choices'],
                'placeholder' => 'Choose a plant',
                'autocomplete' => true,
                'tom_select_options' => [
                    'max-item' => 1,
                    'create' => false,
                ],
            ])
            ->add('file', FileType::class, [
                'label' => 'Upload File:',
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

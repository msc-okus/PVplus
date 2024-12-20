<?php

namespace App\Form\Type;

use App\Form\DataTransformer\StringToNumberTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomNumber extends AbstractType
{
    public function __construct()
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'Non Numeric value inserted',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'custom_number_type';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new StringToNumberTransformer());
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}

<?php

namespace App\Form\Type;

use App\Form\DataTransformer\NameToAnlageTransformer;
use App\Repository\AnlagenRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnlageTextType extends AbstractType
{
    public function __construct(private readonly AnlagenRepository $anlnRepo)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'No plant found by that name',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'anlage_text_type';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new NameToAnlageTransformer($this->anlnRepo));
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}

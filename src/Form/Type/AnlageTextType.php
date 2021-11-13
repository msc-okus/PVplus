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
    private AnlagenRepository $anlnRepo;

    public function __construct(AnlagenRepository $anlRepo)
    {
        $this->anlnRepo = $anlRepo;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'invalid_message'   => 'No plant found by that name'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return "anlage_text_type";
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new NameToAnlageTransformer($this->anlnRepo));
    }

    public function getParent()
    {
        return TextType::class;
    }

}
<?php

namespace App\Form\Type;

use App\Form\DataTransformer\NameToAnlageTransformer;
use App\Form\DataTransformer\StringToNumberTransformer;
use App\Repository\AnlagenRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomNumber extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'invalid_message'=>'Non Numeric value inserted'
        ]);
    }

    public function getBlockPrefix()
    {
        return "custom_number_type";
    }

    public function __construct()
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new StringToNumberTransformer());
    }

    public function getParent()
    {
        return TextType::class;
    }

}

<?php


namespace App\Form\Type;

use App\Form\DataTransformer\DateToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Craue\FormFlowBundle\Form\FormFlow;

class Monthly extends AbstractType
{
    public function __construct()
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'Wrong formatting',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new DateToStringTransformer());
    }

    public function getParent(): ?string
    {
        return Monthly::class;
    }
}
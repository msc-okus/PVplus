<?php

namespace App\Form\Case6;

use App\Entity\Case6Array;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[Deprecated]
class Case6ArrayFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('Case6s', CollectionType::class, [
            'entry_type' => Case6FormType::class,
            'entry_options' => ['label' => false],
        ])
        ->add('save', SubmitType::class, [
        'label' => 'Save',
        'attr' => ['class' => 'primary save'],
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Case6Array::class,
        ]);
    }
}

<?php

namespace App\Form\Owner;

use App\Entity\ContactInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnerContactFormType extends AbstractType
{
    public function __construct(
    )
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('Name', TextType::class, [
        'label'     => 'Name'
        ])
        ->add('companyName', TextType::class, [
            'label'  => 'Name of the company'
        ])
        ->add('Service', TextType::class, [
            'label'     => 'Type of service'
        ])
        ->add('phone', TextType::class, [
            'label'     => 'Phone number'
        ])
        ->add('email', TextType::class, [
            'label'     => 'Email'
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactInfo::class,
        ]);
    }
}
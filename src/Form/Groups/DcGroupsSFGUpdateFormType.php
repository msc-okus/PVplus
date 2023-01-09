<?php

namespace App\Form\Groups;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DcGroupsSFGUpdateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('secureLoss', TextType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Secure Loss',
                ],
                'empty_data'=>null
            ])
            ->add('factorAC', TextType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'DC -> AC[%]'
                ],
                'empty_data'=>null,
            ])
            ->add('gridLoss', TextType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Grid Loss'
                ],
                'empty_data'=>null
            ])
           ->add('term', HiddenType::class,[
              'label'=>false,
              'empty_data'=>null,
               'attr'=>[
                   'data-search-group-target'=>'val'
               ]
           ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}

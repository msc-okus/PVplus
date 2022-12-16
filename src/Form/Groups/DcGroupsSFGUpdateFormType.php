<?php

namespace App\Form\Groups;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DcGroupsSFGUpdateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('secureLoss', TextType::class,[
                'label'=>'Secure Loss',
                'help'=>'update secureLoss for all this AnlageGrps',
                'row_attr'=>['class'=>'cell medium-4 grid-x '],
                'empty_data'=>null
            ])
            ->add('factorAC', TextType::class,[
                'label'=>'DC -> AC[%]',
                'help'=>'update factorAC for all this AnlageGrps',
                'row_attr'=>['class'=>'cell medium-4 '],
                'empty_data'=>null
            ])
            ->add('gridLoss', TextType::class,[
                'label'=>'Grid Loss',
                'help'=>'update gridLoss for all this AnlageGrps',
                'row_attr'=>['class'=>'cell medium-4 '],
                'empty_data'=>null
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

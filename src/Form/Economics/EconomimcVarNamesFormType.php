<?php

namespace App\Form;

use App\Entity\EconomicVarNames;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EconomimcVarNamesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('var_1',TextType::class,[

    ])
            ->add('var_2',TextType::class,[

            ])
            ->add('var_3',TextType::class,[

            ])
            ->add('var_4',TextType::class,[

            ])
            ->add('var_5',TextType::class,[

            ])
            ->add('var_6',TextType::class,[

            ])
            ->add('var_7',TextType::class,[

            ])
            ->add('var_8',TextType::class,[

            ])
            ->add('var_9',TextType::class,[

            ])
            ->add('var_10',TextType::class,[

            ])
            ->add('var_11',TextType::class,[

            ])
            ->add('var_12',TextType::class,[

            ])
            ->add('var_13',TextType::class,[

            ])
            ->add('var_14',TextType::class,[

            ])
            ->add('var_15',TextType::class,[

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EconomicVarNames::class,
        ]);
    }
}

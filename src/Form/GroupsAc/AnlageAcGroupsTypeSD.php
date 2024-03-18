<?php

namespace App\Form\GroupsAc;



use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class AnlageAcGroupsTypeSD extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder
            ->add('searchTerm', SearchType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Search...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }


}

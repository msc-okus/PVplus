<?php

namespace App\Form\Groups;

use App\Entity\AnlageGroupModules;
use App\Entity\AnlageModules;
use App\Repository\ModulesRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupModulsListEmbeddedFormType extends AbstractType
{
    public function __construct(private readonly ModulesRepository $modulesRepo)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $anlagenId = $options['anlagenId'];
        $builder
            ->add('moduleType', EntityType::class, [
                'class' => AnlageModules::class,
                'choice_label' => 'type',
                'choices' => $this->modulesRepo->findBy(['anlage' => $anlagenId]),
            ])
            ->add('numStringsPerUnit', TextType::class, [
                'empty_data' => '0',
            ])
            ->add('numStringsPerUnitEast', TextType::class, [
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('numStringsPerUnitWest', TextType::class, [
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('numModulesPerString', TextType::class, [
                'empty_data' => '0',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageGroupModules::class,
            'anlagenId' => '',
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}

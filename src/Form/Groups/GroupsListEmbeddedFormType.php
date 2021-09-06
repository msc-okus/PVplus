<?php

namespace App\Form\Groups;

use App\Entity\AnlageGroups;
use App\Entity\WeatherStation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupsListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', null, [
                'mapped'        => false,
            ])
            ->add('dcGroup', IntegerType::class, [
                'label'         => 'DC Group',
                'help'          => '[dcGroup]',
                'required'      => true,
            ])
            ->add('dcGroupName', TextType::class, [
                'label'         => 'Group Name (Real Name)',
                'help'          => '[dcGroupName]',
            ])
            ->add('acGroup', IntegerType::class, [
                'label'         => 'AC Group',
                'help'          => '[acGroup]',
                'required'      => true,
            ])
            ->add('unitFirst', IntegerType::class, [
                'label'         => 'First unit (GAK, Inverter, ...)',
                'help'          => '[unitFirst]',
                'required'      => true,
            ])
            ->add('unitLast', IntegerType::class, [
                'label'         => 'Last unit (GAK, Inverter, ...)',
                'help'          => '[unitLast]',
                'required'      => true,
            ])
            ->add('factorAC', TextType::class, [
                'label'         => 'DC -> AC [%]',
                'help'          => '[factorAC]',
                'empty_data'    => '0',
                'required'      => false,
            ])
            ->add('limitAC', TextType::class, [
                'label'         => 'Abriegelung Inverter AC',
                'help'          => '[limitAC]',
                'empty_data'    => '0',
                'required'      => false,
            ])
            ->add('gridLimitAC', TextType::class, [
                'label'         => 'Abriegelung Grid AC',
                'help'          => '[gridLimitAC]',
                'empty_data'    => '0',
                'required'      => false,
            ])
            ->add('irrUpper', TextType::class, [
                'help'          => '[irrUpper]',
                'empty_data'    => '0.5',
                'required'      => false,
            ])
            ->add('irrLower', TextType::class, [
                'help'          => '[irrLower]',
                'empty_data'    => '0.5',
                'required'      => false,
            ])
            ->add('gridLoss', TextType::class, [
                'help'          => '[gridLoss]',
                'empty_data'    => '0',
                'required'      => false,
            ])
            ->add('secureLoss', TextType::class, [
                'help'          => '[secureLoss]',
                'label'         => 'Security loss',
                'empty_data'    => '0',
                'required'      => false,
            ])
            ->add('weatherStation', EntityType::class, [
                'label'         => 'Wetterstation',
                'help'          => '[weatherStation]',
                'class'         => WeatherStation::class,
                'choice_label'  => function(WeatherStation $station) {return sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation());},
                'placeholder'   => 'select a Weatherstation',
                'required'      => false,
                'empty_data'    => null,
            ])

            ################################################
            ####              Relations                 ####
            ################################################

            ->add('modules', CollectionType::class, [
                'entry_type'    => GroupModulsListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
                'entry_options' => ['anlagenId' => $options['anlagenId']],
            ])
            ->add('months', CollectionType::class, [
                'entry_type'    => MonthsListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageGroups::class,
            'anlagenId' => '',
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}

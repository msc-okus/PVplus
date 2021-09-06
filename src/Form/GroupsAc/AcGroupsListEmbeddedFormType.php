<?php

namespace App\Form\GroupsAc;

use App\Entity\AnlageAcGroups;
use App\Entity\WeatherStation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcGroupsListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('acGroup')
            ->add('acGroupName', TextType::class, [
                'help'          => '[acGroupName]',
                'empty_data'    => '',
            ])
            ->add('unitFirst', TextType::class, [
                'help'          => '[unitFirst]',
                'empty_data'    => '',
            ])
            ->add('unitLast', TextType::class, [
                'help'          => '[unitLast]',
                'empty_data'    => '',
            ])
            ->add('dcPowerInverter', TextType::class, [
                'required'      => false,
                'help'          => '[dcPowerInverter]',
                'empty_data'    => '0',
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
            ->add('isEastWestGroup', ChoiceType::class, [
                'required'      => false,
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'help'          => '[isEastWestGroup]',
                'empty_data'    => false,
            ])
            ->add('gewichtungAnlagenPR', TextType::class, [
                'required'      => false,
                'help'          => '[gewichtungAnlagenPR]',
                'empty_data'    => '',
            ])
            ->add('tCellAvg', TextType::class, [
                'required'      => false,
                'help'          => '[tCellAvg]',
                'empty_data'    => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageAcGroups::class,
        ]);
    }
}


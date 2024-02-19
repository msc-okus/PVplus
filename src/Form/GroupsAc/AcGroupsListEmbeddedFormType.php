<?php

namespace App\Form\GroupsAc;

use App\Entity\AnlageAcGroups;
use App\Entity\WeatherStation;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcGroupsListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('acGroup',TextType::class,[
                 'help' => '[The Number of Trafostation]',
                'empty_data' => '',
                'attr' => ['maxlength' => 4, 'style' => 'width: 55px'],
                ])
            ->add('trafoNr', TextType::class, [
                'help' => '[The Number of Trafostation]',
                'empty_data' => '',
                'attr' => ['maxlength' => 4, 'style' => 'width: 55px'],
            ])
            ->add('acGroupName', TextType::class, [
                'help' => '[acGroupName]',
                'empty_data' => '',
                'attr' => ['maxlength' => 35, 'style' => 'width: 255px'],
            ])
            ->add('unitFirst', TextType::class, [
                'help' => '[unitFirst]',
                'empty_data' => '',
                'attr' => ['maxlength' => 4, 'style' => 'width: 55px'],
            ])
            ->add('unitLast', TextType::class, [
                'help' => '[unitLast]',
                'empty_data' => '',
                'attr' => ['maxlength' => 4, 'style' => 'width: 55px'],
            ])
            ->add('dcPowerInverter', TextType::class, [
                'required' => false,
                'help' => '[dcPowerInverter]',
                'empty_data' => '0',
                'attr' => ['maxlength' => 8, 'style' => 'width: 75px'],
            ])
            /* wird nicht mehr benötigt - MS
            ->add('weatherStation', EntityType::class, [
                'label' => 'Wetterstation',
                'help' => '[weatherStation]',
                'class' => WeatherStation::class,
                'choice_label' => fn(WeatherStation $station) => sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation()),
                'placeholder' => 'select a Weatherstation',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('isEastWestGroup', ChoiceType::class, [
                'required' => false,
                'choices' => ['Yes' => '1', 'No' => '0'],
                'help' => '[isEastWestGroup]',
                'empty_data' => '0',
            ])
            ->add('pyro1', TextType::class, [
                'required' => false,
                'help' => '[pyro1]',
            ])
            ->add('pyro2', TextType::class, [
                'required' => false,
                'help' => '[pyro1]',
            ])
            ->add('powerEast',TextType::class, [
                'required' => false,
                'help' => '[powerEast]',
                'empty_data' => '0.5',
            ])
            ->add('powerWest',TextType::class, [
                'required' => false,
                'help' => '[powerWest]',
                'empty_data' => '0.5',
            ])
            ->add('gewichtungAnlagenPR', TextType::class, [
                'required' => false,
                'help' => '[gewichtungAnlagenPR]',
                'empty_data' => '',
            ])
        */
            ->add('tCellAvg', TextType::class, [
                'required' => false,
                'help' => '[tCellAvg]',
                'empty_data' => '',
                'attr' => ['maxlength' => 6, 'style' => 'width: 75px'],
            ])
            ->add('importId', TextType::class, [
                'required' => false,
                'help' => '[importId]',
                'empty_data' => '',
                'attr' => ['maxlength' => 18, 'style' => 'width: 175px'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageAcGroups::class,
        ]);
    }
}

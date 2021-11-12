<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\WeatherStation;
use App\Form\EventMail\EventMailListEmbeddedFormType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AnlageConfigFormType extends AbstractType
{
    private $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $timearray = [
            '+5'    => '+5',
            '+4'    => '+4',
            '+3.75' => '+3.75',
            '+3.50' => '+3.50',
            '+3.25' => '+3.25',
            '+3'    => '+3',
            '+2.75' => '+2.75',
            '+2.50' => '+2.50',
            '+2.25' => '+2.25',
            '+2'    => '+2',
            '+1.75' => '+1.75',
            '+1.50' => '+1.50',
            '+1.25' => '+1.25',
            '+1'    => '+1',
            '+0.75' => '+0.75',
            '+0.50' => '+0.50',
            '+0.25' => '+0.25',
            '+0'    => '+0',
            '-0.25' => '-0.25',
            '-0.50' => '-0.50',
            '-0.75' => '-0.75',
            '-1'    => '-1',
            '-1.25' => '-1.25',
            '-1.50' => '-1.50',
            '-1.75' => '-1.75',
            '-2'    => '-2',
            '-2.25' => '-2.25',
            '-2.50' => '-2.50',
            '-2.75' => '-2.75',
            '-3'    => '-3',
            '-4'    => '-4',
            '-5'    => '-5',
        ];

        $builder
            ->add('anlName', TextType::class, [
                'label'         => 'Anlagen Name',
                'help'          => '[anlName]',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('projektNr', TextType::class, [
                'label'         => 'Projekt Nummer',
                'help'          => '[projektNr]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlStrasse', TextType::class, [
                'label'         => 'Strasse',
                'help'          => '[anlStrasse]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlPlz', TextType::class, [
                'label'         => 'PLZ',
                'help'          => '[anlPlz]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlOrt', TextType::class, [
                'label'         => 'Ort',
                'help'          => '[anlOrt]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('country', TextType::class, [
                'label'         => 'Land als Kürzel (de, nl, ...)',
                'help'          => '[country]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlGeoLat', TextType::class, [
                'label'         => 'Geografische Breite (Latitude) [Dezimalgrad]',
                'help'          => '[anlGeoLat]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlGeoLon', TextType::class, [
                'label'         => 'Geografische Länge (Longitude) [Dezimalgrad]',
                'help'          => '[anlGeoLon]',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('notes', TextareaType::class, [
                'label'         => 'Notizen zur Anlage',
                'attr'          => ['rows' => '6'],
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('epcReportNote', TextareaType::class, [
                'label'         => 'Notizen zur Anlage für EPC Report',
                'attr'          => ['rows' => '9'],
                'empty_data'    => '',
                'required'      => false,
            ])

            ->add('sendWarnMail', ChoiceType::class, [
                'label'         => 'Sende Warn E-Mails',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'empty_data'    => '0',
            ])
            ->add('var_1', TextType::class, [
                'data'          => '',
                'label'         => 'Variable 1',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_2', TextType::class, [
                'label'         => 'Variable 2',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_3', TextType::class, [
                'label'         => 'Variable 3',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_4', TextType::class, [
                'label'         => 'Variable 4',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_5', TextType::class, [
                'label'         => 'Variable 5',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_6', TextType::class, [
                'label'         => 'Variable 6',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_7', TextType::class, [
                'label'         => 'Variable 7',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_8', TextType::class, [
                'label'         => 'Variable 8',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_9', TextType::class, [
                'label'         => 'Variable 9',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_10', TextType::class, [
                'label'         => 'Variable 10',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_11', TextType::class, [
                'label'         => 'Variable 11',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_12', TextType::class, [
                'label'         => 'Variable 12',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_13', TextType::class, [
                'label'         => 'Variable 13',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_14', TextType::class, [
                'label'         => 'Variable 14',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])

            ->add('var_15', TextType::class, [
                'label'         => 'Variable 15',
                'empty_data'    => '',
                'required'      => false,
                'mapped'        => false,
            ])
            ################################################
            ####              Relations                 ####
            ################################################
            ->add('eventMails', CollectionType::class, [
                'entry_type'    => EventMailListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ->add('legendMonthlyReports', CollectionType::class, [
                'entry_type'    => MonthlyLegendListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ->add('legendEpcReports', CollectionType::class, [
                'entry_type'    => EpcLegendListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ->add('pvSystMonths', CollectionType::class, [
                'entry_type'    => PvSystMonthListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ->add('monthlyYields', CollectionType::class, [
                'entry_type'    => MonthlyYieldListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])

            ->add('economicVarValues', CollectionType::class, [
                'entry_type'    => EconomicVarsValuesEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################
            ->add('save', SubmitType::class, [
                'label' => 'Save Plant',
                'attr'  => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close Plant',
                'attr'  => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close without save',
                'attr'  => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
        ;

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => Anlage::class,
            'anlagenId'     => '',
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}
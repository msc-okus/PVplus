<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\WeatherStation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AnlageNewFormType extends AbstractType
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
            ->add('eigner', EntityType::class, [
                'class'         => Eigner::class,
                'choice_label'  => 'firma',
                'required'      => true,
            ])

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


            ->add('anlIntnr', TextType::class, [
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('anlDbUnit', ChoiceType::class, [
                'label'         => 'Einheit in der die Anlagen Daten gespeichert werden.',
                'choices'       => ['W' => 'w', 'kWh' => 'kwh'],
                'empty_data'    => 'kWh',
                'required'      => false,
            ])
            ->add('useNewDcSchema', ChoiceType::class, [
                'label'         => 'Aktiviere neues DC Database Schema (separate Tabelle für DC IST Werte)',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
                'required'      => false,
            ])
            ->add('anlType', ChoiceType::class, [
                'choices'       => ['String WR' => 'string', 'ZWR' => 'zwr', 'Master Slave' => 'masterslave'],
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('anlZeitzone', ChoiceType::class, [
                'label'         => 'Zeit Korrektur Anlage',
                'choices'       => $timearray,
                'data'          => '+0',
                'empty_data'    => '+0',
            ])
            ->add('anlInputDaily', ChoiceType::class, [
                'label'         => 'Anlage erhält nur einmal am Tag neue Daten',
                'choices'       => ['Yes' => 'Yes', 'No' => 'No'],
                'data'          => 'No',
                'empty_data'    => 'No',
            ])
            ->add('anlBetrieb', null, [
                'widget'        => 'single_text',
                'data'          => new \DateTime('now'),
                'required'      => true,

            ])

            ->add('WeatherStation', EntityType::class, [
                'label'         => 'Wetterstation',
                'class'         => WeatherStation::class,
                'choice_label'  => 'databaseIdent',
                'required'      => true,
            ])


            ->add('power', TextType::class, [
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlDbUnit', ChoiceType::class, [
                'label'         => 'Einheit in der die Anlagen Daten gespeichert werden.',
                'choices'       => ['kWh' => 'kwh', 'W' => 'w'],
                'data'          => 'kWh',
                'empty_data'    => 'kWh',
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
            'data_class' => Anlage::class,
        ]);
    }
}

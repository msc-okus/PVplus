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



            /*
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
            ->add('configType', ChoiceType::class, [
                'label'         => 'Configuration der Anlage',
                'help'          => '[configType]',
                'choices'       => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'placeholder'   => 'Please Choose',
                'empty_data'    => 1,
            ])
            */
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
        $prArray = [
            'No Cust PR'                => 'no',
            'Groningen'                 => 'Groningen',
            'Veendam'                   => 'Veendam',
            'Lelystad (Temp Korrektur)' => 'Lelystad',
        ];
        $epcReportArry = [
            'Kein Bericht'      => 'no',
            'PR Garantie'       => 'prGuarantee',
            'Ertrags Garantie'  => 'yieldGuarantee',
        ];
        $pldDiviorArray = [
            'Expected Energy'               => 'expected',
            'Guaranteed Expected Energy'    => 'guaranteedExpected',
        ];
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $builder
            ################################################
            ####                General                 ####
            ################################################

            ###### Plant Location #######
            ->add('eigner', EntityType::class, [
                'label'         => 'Eigner',
                'help'          => '[eigner]',
                'class'         => Eigner::class,
                'choice_label'  => 'firma',
                'required'      => true,
                'disabled'      => !$isDeveloper,
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

            ###### Plant Base Configuration #######
            ->add('anlIntnr', TextType::class, [
                'label'         => 'Datenbankkennung',
                'help'          => '[anlIntnr]',
                'empty_data'    => '',
                'required'      => true,
                'disabled'      => !$isDeveloper,
            ])

            ->add('anlType', ChoiceType::class, [
                'label'         => 'Anlagen Typ',
                'help'          => '[anlType]',
                'choices'       => ['String WR' => 'string', 'ZWR' => 'zwr', 'Master Slave' => 'masterslave'],
                'placeholder'   => 'Please Choose',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('anlBetrieb', null, [
                'label'         => 'In Betrieb seit:',
                'help'          => '[anlBetrieb]',
                'widget'        => 'single_text',
                'input'         => 'datetime',

            ])
            ->add('anlZeitzone', ChoiceType::class, [
                'label'         => 'Zeit Korrektur Anlage',
                'help'          => '[anlZeitzone]',
                'choices'       => $timearray,
                'placeholder'   => 'Please Choose',
                'empty_data'    => '+0',
            ])
            ->add('anlInputDaily', ChoiceType::class, [
                'label'         => 'Nur einmal am Tag neue Daten',
                'help'          => '[anlInputDaily]',
                'choices'       => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder'   => 'Please Choose',
                'empty_data'    => 'No',
            ])
            ->add('configType', ChoiceType::class, [
                'label'         => 'Configuration der Anlage',
                'help'          => '[configType]',
                'choices'       => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'placeholder'   => 'Please Choose',
                'empty_data'    => 1,
                'disabled'      => !$isDeveloper,
            ]);

        if ($this->security->isGranted('ROLE_DEV')) {
            $builder
                ->add('useNewDcSchema', ChoiceType::class, [
                    'label'         => 'Neues DC Database Schema (separate Tabelle für DC IST)',
                    'help'          => '[useNewDcSchema]',
                    'choices'       => ['Yes' => '1', 'No' => '0'],
                    'empty_data'    => '0',
                    'expanded'      => false,
                    'multiple'      => false,
                ])
            ;
        }

        $builder
            ###### WeatherStation #######
            ->add('WeatherStation', EntityType::class, [
                'label'         => 'Wetterstation',
                'help'          => '[WeatherStation]',
                'class'         => WeatherStation::class,
                'choice_label'  => function(WeatherStation $station) {return sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation());},
                'required'      => true,
                'disabled'      => !$isDeveloper,
            ])
            ->add('useLowerIrrForExpected', ChoiceType::class, [
                'label'         => 'Benutze \'IrrLower\' für die Berechnung Expected',
                'help'          => '[useLowerIrrForExpected]',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '0',
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

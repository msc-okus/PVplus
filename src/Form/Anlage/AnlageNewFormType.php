<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use Carbon\Doctrine\DateTimeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class AnlageNewFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin     = $this->security->isGranted('ROLE_ADMIN');
        $builder
            // ###############################################
            // ###                General                 ####
            // ###############################################

            // ##### Plant Location #######
            ->add('eigner', EntityType::class, [
                'label' => 'Eigner',
                'help' => '[eigner]',
                'class' => Eigner::class,
                'choice_label' => 'firma',
                'required' => true,
                'disabled' => false,
            ])
            ->add('anlName', TextType::class, [
                'label' => 'Anlagen Name',
                'help' => '[anlName]',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('projektNr', TextType::class, [
                'label' => 'Projekt Nummer',
                'help' => '[projektNr]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlStrasse', TextType::class, [
                'label' => 'Strasse',
                'help' => '[anlStrasse]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlPlz', TextType::class, [
                'label' => 'PLZ',
                'help' => '[anlPlz]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlOrt', TextType::class, [
                'label' => 'Ort',
                'help' => '[anlOrt]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('country', TextType::class, [
                'label' => 'Land als Kürzel (de, nl, ...)',
                'help' => '[country]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlGeoLat', TextType::class, [
                'label' => 'Geografische Breite (Latitude) [Dezimalgrad]',
                'help' => '[anlGeoLat]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlGeoLon', TextType::class, [
                'label' => 'Geografische Länge (Longitude) [Dezimalgrad]',
                'help' => '[anlGeoLon]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notizen zur Anlage',
                'attr' => ['rows' => '6'],
                'empty_data' => '',
                'required' => false,
            ])

            // ##### Plant Base Configuration #######
            ->add('anlIntnr', TextType::class, [
                'label' => 'Datenbankkennung',
                'help' => '[anlIntnr]',
                'empty_data' => '',
                'required' => true,
                'disabled' => !$isDeveloper,
            ])

            ->add('anlType', ChoiceType::class, [
                'label' => 'Anlagen Typ',
                'help' => '[anlType]',
                'choices' => ['String WR' => 'string', 'ZWR' => 'zwr', 'Master Slave' => 'masterslave'],
                'placeholder' => 'Please Choose',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('anlBetrieb', null, [
                'label' => 'In Betrieb seit:',
                'help' => '[anlBetrieb]<br>Wird für die Berechnung der Degradation benötigt',
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('anlZeitzone', ChoiceType::class, [
                'label' => 'Zeit Korrektur Anlage',
                'help' => '[anlZeitzone]',
                'choices' => self::timeArray(),
                'placeholder' => 'Please Choose',
                'empty_data' => '+0',
            ])
            ->add('anlInputDaily', ChoiceType::class, [
                'label' => 'Nur einmal am Tag neue Daten',
                'help' => '[anlInputDaily]',
                'choices' => ['Yes' => 'Yes', 'No' => 'No'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'No',
                'disabled' => !($isDeveloper),
            ])
            ->add('useNewDcSchema', ChoiceType::class, [
                'label' => 'Neues DC Database Schema (separate Tabelle für DC IST)',
                'help' => '[useNewDcSchema]',
                'choices' => ['Yes' => '1', 'No' => '0'],
                'empty_data' => '0',
                'expanded' => false,
                'multiple' => false,
                'disabled' => !($isDeveloper),
            ])
            ->add('configType', ChoiceType::class, [
                'label' => 'Configuration der Anlage',
                'help' => '[configType]',
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'placeholder' => 'Please Choose',
                'empty_data' => 1,
                'disabled' => !($isDeveloper || $isAdmin),
            ])
            // ##### WeatherStation #######
            ->add('WeatherStation', EntityType::class, [
                'label' => 'Wetterstation',
                'help' => '[WeatherStation]',
                'class' => WeatherStation::class,
                'choice_label' => function (WeatherStation $station) {return sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation()); },
                'placeholder' => 'Please Choose',
                'required' => true,
                'empty_data' => null,
                #'disabled' => !$isDeveloper,
            ])
            /*
            ->add('useLowerIrrForExpected', ChoiceType::class, [
                'label' => 'Benutze \'IrrLower\' für die Berechnung Expected',
                'help' => '[useLowerIrrForExpected]',
                'choices' => ['Yes' => '1', 'No' => '0'],
                'empty_data' => '0',
            ]) */

            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################
            ->add('save', SubmitType::class, [
                'label' => 'Save Plant',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close Plant',
                'attr' => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close without save',
                'attr' => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
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

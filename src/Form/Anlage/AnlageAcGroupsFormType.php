<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\WeatherStation;
use App\Form\EventMail\EventMailListEmbeddedFormType;
use App\Form\Groups\GroupsListEmbeddedFormType;
use App\Form\GroupsAc\AcGroupsListEmbeddedFormType;
use App\Helper\G4NTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Security\Core\Security;

class AnlageAcGroupsFormType extends AbstractType
{
    use G4NTrait;

    private $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

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

            
            ################################################
            ####              Relations                 ####
            ################################################

            ->add('acGroups', CollectionType::class, [
                'entry_type'    => AcGroupsListEmbeddedFormType::class,
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
            ->add('savecreatedb', SubmitType::class, [
                'label'         => 'Save and Create Databases',
                'attr'  => ['class' => 'secondary small', 'formnovalidate' => 'formnovalidate'],
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

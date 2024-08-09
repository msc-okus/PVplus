<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnlageNewFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                'label' => 'Plant Name',
                'help' =>  '[anlName]<br>The Name of the Plant',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('projektNr', TextType::class, [
                'label' => 'Project Nummer',
                'help' => '[projektNr]<br>optional Project No',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlStrasse', TextType::class, [
                'label' => 'Street',
                'help' => '[anlStrasse]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlPlz', TextType::class, [
                'label' => 'ZIP Code',
                'help' => '[anlPlz]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlOrt', TextType::class, [
                'label' => 'City',
                'help' => '[anlOrt]',
                'empty_data' => '',
                'required' => false,
            ])
             ->add('country', ChoiceType::class, [
                 'label' => 'Shortcut for the country (de, nl, ...)',
                 'help' => '[country]',
                 'empty_data' => '',
                 'required' => false,
                 'choices' => $this->countryCodes(),
             ])
            ->add('anlGeoLat', TextType::class, [
                'label' => 'Latitude [Decimal notation]',
                'help' => '[anlGeoLat]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlGeoLon', TextType::class, [
                'label' => 'Longitude [Decimal notation]',
                'help' => '[anlGeoLon]',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('customPlantId', TextType::class, [
                'label' => 'Identifier/s to select Plant via API',
                'help' => '[customPlantId]<br> Can be more then one ID, seperatet with: comma. <br>Example: ABC2X,CDE3F]',
                'empty_data' => '',
                'required' => false,
                'disabled' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notizen zur Anlage',
                'help' => '[notes]',
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
                'choice_label' => fn(WeatherStation $station) => sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation()),
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Anlage::class,
        ]);
    }
}

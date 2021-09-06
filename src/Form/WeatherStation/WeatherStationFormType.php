<?php
namespace App\Form\WeatherStation;

use App\Entity\WeatherStation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WeatherStationFormType extends AbstractType
{
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

        /** @var WeatherStation $station */
        $station = $options['data'] ?? null;
        $isEdit = $station && $station->getId();

        $builder
            ->add('type', ChoiceType::class, [
                'choices'       => [
                    'UP old'        => 'UPold',
                    'UP new'        => 'UPnew',
                    'UP v1120'      => 'UPv1120',
                    'custom'        => 'custom',
                ],
                'label'         => 'Weather Station type',
                'empty_data'    => '',
            ])

            ->add('databaseIdent', TextType::class, [
                'label'         => 'Database Ident Code',
                'empty_data'    => '',
            ])
            ->add('location', TextType::class, [
                'label'         => 'Location',
                'empty_data'    => '',
            ])
            ->add('description', TextareaType::class, [
                'label'         => 'Description',
                'empty_data'    => '',
            ])
            ->add('hasUpper', ChoiceType::class, [
                'label'         => 'hat oberen Sensor (Ost Sensor)',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'help'          => '[hasUpper] – Feld in WS DB: [g_upper]',
                'empty_data'    => '1',
            ])
            ->add('hasLower', ChoiceType::class, [
                'label'         => 'hat unteren Sensor (West Sensor)',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'help'          => '[hasLower] – Feld in WS DB: [g_lower]',
                'empty_data'    => '0',
            ])
            ->add('hasHorizontal', ChoiceType::class, [
                'label'         => 'hat horizontalen Sensor ',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'help'          => '[hasHorizontal] – Feld in WS DB: [g_horizontal]',
                'empty_data'    => '0',
            ])
            ->add('changeSensor', ChoiceType::class, [
                'label'         => 'Sensoren sind getauscht',
                'choices'       => ['No' => '0', 'Yes' => '1'],
                'empty_data'    => '0',
            ])
            ->add('hasPannelTemp', ChoiceType::class, [
                'label'         => 'hat Sensoren für Pannel Temeratur',
                'choices'       => ['Yes' => '1', 'No' => '0'],
                'empty_data'    => '1',
            ])
            ->add('timeZoneWeatherStation', ChoiceType::class, [
                'label'         => 'Zeitzone Wetterstation',
                'choices'       => $timearray,
                'empty_data'    => '+0',
                'placeholder'   => 'Please Choose',
            ])
            ->add('labelUpper', TextType::class, [
                'label'         => 'Beschreibung für Irr Upper',
                'empty_data'    => 'Incident upper table [W/qm]',
                'help'          => 'labelUpper',
            ])
            ->add('labelLower', TextType::class, [
                'label'         => 'Beschreibung für Irr Lower',
                'empty_data'    => 'Incident lower table [W/qm]',
                'help'          => 'labelLower',
            ])
            ->add('labelHorizontal', TextType::class, [
                'label'         => 'Beschreibung für Irr Horizontal',
                'empty_data'    => 'Incident horizontal table [W/qm]',
                'help'          => 'labelHorizontal',
            ])

            ->add('save', SubmitType::class, [
                'label'     => 'Save Weather Station',
                'attr'      => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label'     => 'Save and Close Weather Station',
                'attr'      => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label'     => 'Close without save',
                'attr'      => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WeatherStation::class
        ]);
    }


}
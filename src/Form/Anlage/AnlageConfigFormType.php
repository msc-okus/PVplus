<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Form\EventMail\EventMailListEmbeddedFormType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class AnlageConfigFormType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $variable_lenght = 20;

        $builder
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

            ->add('epcReportNote', CKEditorType::class, [
                'label' => 'Notes attached to the EPC Report',
                'config' => ['toolbar' => 'my_toolbar'],
                'attr' => ['rows' => '9'],
                'empty_data' => '',
                'required' => false,
            ])

            ->add('sendWarnMail', ChoiceType::class, [
                'label' => 'Sende Warn E-Mails',
                'choices' => ['No' => '0', 'Yes' => '1'],
                'empty_data' => '0',
            ])
            ->add('picture', FileType::class, [
                'label' => 'Picture',
                'mapped' => false,
                'attr' => [
                    'accept' => '.jpeg, .gif, .png, .gif, .jpg, .svg',
                ],
                'constraints' => [
                    new image([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/jpg',
                            'image/svg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image type(jpeg, png, gif, jpg, svg)',
                    ]),
                ],
            ])
        ;

        if ($this->security->isGranted('ROLE_AM')) {
            $builder
                ->add('var_1', TextType::class, [
                    'label' => 'Variable 1',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_2', TextType::class, [
                    'label' => 'Variable 2',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_3', TextType::class, [
                    'label' => 'Variable 3',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_4', TextType::class, [
                    'label' => 'Variable 4',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_5', TextType::class, [
                    'label' => 'Variable 5',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_6', TextType::class, [
                    'label' => 'Variable 6',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_7', TextType::class, [
                    'label' => 'Variable 7',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_8', TextType::class, [
                    'label' => 'Variable 8',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_9', TextType::class, [
                    'label' => 'Variable 9',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_10', TextType::class, [
                    'label' => 'Variable 10',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_11', TextType::class, [
                    'label' => 'Variable 11',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_12', TextType::class, [
                    'label' => 'Variable 12',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_13', TextType::class, [
                    'label' => 'Variable 13',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_14', TextType::class, [
                    'label' => 'Variable 14',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_15', TextType::class, [
                    'label' => 'Variable 15',
                    'attr' => ['maxlength' => $variable_lenght],
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
            ;
        }

        // ###############################################
        // ###              Relations                 ####
        // ###############################################
        $builder
            ->add('eventMails', CollectionType::class, [
                'entry_type' => EventMailListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
            ->add('legendMonthlyReports', CollectionType::class, [
                'entry_type' => MonthlyLegendListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
            ->add('legendEpcReports', CollectionType::class, [
                'entry_type' => EpcLegendListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
            ->add('pvSystMonths', CollectionType::class, [
                'entry_type' => PvSystMonthListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
            ->add('monthlyYields', CollectionType::class, [
                'entry_type' => MonthlyYieldListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
            ->add('infoFiles', FileType::class, [
                'label' => ' ',
                'multiple' => 'multiple',
                'mapped'      => false
            ])

        ;
        if ($this->security->isGranted('ROLE_AM')) {
            $builder
                ->add('economicVarValues', CollectionType::class, [
                    'entry_type' => EconomicVarsValuesEmbeddedFormType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'by_reference' => false,
                ])
            ;
        }

        // #############################################
        // ###          STEUERELEMENTE              ####
        // #############################################
        $builder
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
            'anlagenId' => '',
            'required' => false,
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}

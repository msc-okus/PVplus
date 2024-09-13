<?php

namespace App\Form\Owner;

use App\Entity\Eigner;
use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class OwnerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Eigner $owner */
        $owner = $options['data'] ?? null;
        $isEdit = $owner && $owner->getEignerId();

        $builder
            ->add('firma', TextType::class, [
                'label' => 'Company',
                'empty_data' => '',
            ])
            ->add('zusatz', TextType::class, [
                'label' => 'Additional Information',
                'empty_data' => '',
            ])
            ->add('plz', TextType::class, [
                'label' => 'ZIP Code',
                'empty_data' => '',
            ])
            ->add('ort', TextType::class, [
                'label' => 'City',
                'empty_data' => '',
            ])
            ->add('strasse', TextType::class, [
                'label' => 'Address',
                'empty_data' => '',
            ])
            ->add('anrede', ChoiceType::class, [
                'choices' => [
                    'Mr.' => 'Mr.',
                    'Mrs.' => 'Mrs.',
                ],
                'label' => 'Salutation',
            ])
            ->add('vorname', TextType::class, [
                'label' => 'Firstname',
                'empty_data' => '',
            ])
            ->add('nachname', TextType::class, [
                'label' => 'Lastname',
                'empty_data' => '',
            ])
            ->add('active', ChoiceType::class, [
                'label' => 'Eigner aktiv ?',
                'choices' => ['Yes' => '1', 'No' => '0'],
                'placeholder' => 'Please Choose',
                'empty_data' => '1',
            ])
            ->add('language', ChoiceType::class, [
                'label' => 'Sprache (im Moment nur EN)',
                'choices' => ['EN' => 'EN', 'DE' => 'DE'],
                'placeholder' => 'Please Choose',
                'empty_data' => 'EN',
            ])
            ->add('operations', SwitchType::class,[
                'label' => 'company is operated by G4N'
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Logo',
                'mapped' => false,
                'constraints' => [
                    new image([
                        'maxSize' => '5M',
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

            // #############################################
            // ###             Features                 ####
            // #############################################

            ->add('features', OwnerFeaturesFormType::class, [
                'label' => 'Features',
            ])


            // #############################################
            // ###              Settings                ####
            // #############################################

            ->add('settings', OwnerSettingsFormType::class, [
                'label' => 'Settings',
            ])

            // #############################################
            // ###              Contacts                ####
            // #############################################
            ->add('ApiConfig', CollectionType::class, [
                'entry_type' => OwnerApiFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])

            // #############################################
            // ###              Contacts                ####
            // #############################################
            ->add('ContactInfos', CollectionType::class, [
                'entry_type' => OwnerContactFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])

            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################
            ->add('save', SubmitType::class, [
                'label' => 'Save Owner',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close Owner',
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
            'data_class' => Eigner::class,
        ]);
    }
}

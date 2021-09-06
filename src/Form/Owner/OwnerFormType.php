<?php
namespace App\Form\Owner;

use App\Entity\Eigner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Eigner $owner */
        $owner = $options['data'] ?? null;
        $isEdit = $owner && $owner->getEignerId();

        $builder
            ->add('firma', TextType::class, [
                'label'         => 'Company',
                'empty_data'    => '',
            ])
            ->add('zusatz', TextType::class, [
                'label'         => 'Additional Information',
                'empty_data'    => '',
            ])
            ->add('plz', TextType::class, [
                'label'         => 'ZIP Code',
                'empty_data'    => '',
            ])
            ->add('ort', TextType::class, [
                'label'         => 'City',
                'empty_data'    => '',
            ])
            ->add('strasse', TextType::class, [
                'label'         => 'Adress',
                'empty_data'    => '',
            ])
            ->add('anrede', ChoiceType::class, [
                'choices'       => [
                    'Mr.'   => 'Mr.',
                    'Mrs.'  => 'Mrs.'
                ],
                'label'         => 'Salutation',
            ])
            ->add('vorname', TextType::class, [
                'label'         => 'Firstname',
                'empty_data'    => '',
            ])
            ->add('nachname', TextType::class, [
                'label'         => 'Lastname',
                'empty_data'    => '',
            ])
            ->add('telefon1', TextType::class, [
                'label'         => 'Phone 1',
                'empty_data'    => '',
            ])
            ->add('telefon2', TextType::class, [
                'label'         => 'Phone 2',
                'empty_data'    => '',
            ])
            ->add('mobil', TextType::class, [
                'label'         => 'Mobile Phone',
                'empty_data'    => '',
            ])
            ->add('fax', TextType::class, [
                'label'         => 'Fax',
                'empty_data'    => '',
            ])
            ->add('email', TextType::class, [
                'label'         => 'eMail',
                'empty_data'    => '',
            ])

            ->add('bv_anrede', ChoiceType::class, [
                'label'         => 'Operator Salutation',
                'choices'       => [
                    'Mr.'   => 'Mr.',
                    'Mrs.'  => 'Mrs.'
                ],
            ])
            ->add('bv_vorname', TextType::class, [
                'label'         => 'Operator Firstname',
                'empty_data' => '',
            ])
            ->add('bv_nachname', TextType::class, [
                'label'         => 'Operator Lastname',
                'empty_data' => '',
            ])
            ->add('bv_email', TextType::class, [
                'label'         => 'Operator eMail',
                'empty_data' => '',
            ])
            ->add('bv_telefon1', TextType::class, [
                'label'         => 'Operator Phone 1',
                'empty_data' => '',
            ])
            ->add('bv_telefon2', TextType::class, [
                'label'         => 'Operator Phone 2',
                'empty_data' => '',
            ])
            ->add('bv_mobil', TextType::class, [
                'label'         => 'Operator Mobile',
                'empty_data' => '',
            ])

            ->add('active', ChoiceType::class, [
                'label'     => 'Eigner aktiv ?',
                'choices'  => ['Yes' => '1', 'No' => '0'],
                'placeholder' => 'Please Choose',
                'empty_data'    => '1',
            ])
            ->add('editlock', ChoiceType::class, [
                'label'     => '?? deprecatetd',
                'choices'  => ['Yes' => '1', 'No' => '0'],
                'placeholder' => 'Please Choose',
                'empty_data'    => '0',
            ])
            ->add('userlock', ChoiceType::class, [
                'label'     => '?? deprecatetd',
                'choices'  => ['Yes' => '1', 'No' => '0'],
                'placeholder' => 'Please Choose',
                'empty_data'    => '0',
            ])
            ->add('language', ChoiceType::class, [
                'label'     => 'Sprache (im Moment nur EN)',
                'choices'  => ['EN' => 'EN', 'DE' => 'DE'],
                'placeholder' => 'Please Choose',
                'empty_data'    => 'EN',
            ])


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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Eigner::class
        ]);
    }


}
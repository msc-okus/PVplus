<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class AnlageCustomerFormType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anlName', TextType::class, [
                'label' => 'Project Name',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('projektNr', TextType::class, [
                'label' => 'Project No',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('anlStrasse', TextType::class, [
                'label' => 'Street',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlPlz', TextType::class, [
                'label' => 'ZIP Code',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlOrt', TextType::class, [
                'label' => 'City',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('epcReportNote', CKEditorType::class, [
                'config' => ['toolbar' => 'my_toolbar'],
                'label' => 'Notes attached to the EPC Report',
                'attr' => ['rows' => '9'],
                'empty_data' => '',
                'required' => false,
            ])


            // ###############################################
            // ###              Relations                 ####
            // ###############################################

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
                'allow_add' => false,
                'allow_delete' => false,
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
        ;
        if ($this->security->isGranted('ROLE_AM')) {
            $builder
                ->add('var_1', TextType::class, [
                    'data' => '',
                    'label' => 'Variable 1',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_2', TextType::class, [
                    'label' => 'Variable 2',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_3', TextType::class, [
                    'label' => 'Variable 3',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_4', TextType::class, [
                    'label' => 'Variable 4',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_5', TextType::class, [
                    'label' => 'Variable 5',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_6', TextType::class, [
                    'label' => 'Variable 6',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_7', TextType::class, [
                    'label' => 'Variable 7',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_8', TextType::class, [
                    'label' => 'Variable 8',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_9', TextType::class, [
                    'label' => 'Variable 9',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_10', TextType::class, [
                    'label' => 'Variable 10',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_11', TextType::class, [
                    'label' => 'Variable 11',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_12', TextType::class, [
                    'label' => 'Variable 12',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_13', TextType::class, [
                    'label' => 'Variable 13',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_14', TextType::class, [
                    'label' => 'Variable 14',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('var_15', TextType::class, [
                    'label' => 'Variable 15',
                    'empty_data' => '',
                    'required' => false,
                    'mapped' => false,
                ])
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
        ]);
    }
}

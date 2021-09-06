<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AnlageCustomerFormType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('anlName', TextType::class, [
                'label'         => 'Project Name',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('projektNr', TextType::class, [
                'label'         => 'Project No',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('anlStrasse', TextType::class, [
                'label'         => 'Street',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlPlz', TextType::class, [
                'label'         => 'ZIP Code',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlOrt', TextType::class, [
                'label'         => 'City',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('epcReportNote', TextareaType::class, [
                'label'         => 'Notizen zur Anlage für EPC Report',
                'attr'          => ['rows' => '9'],
                'empty_data'    => '',
                'required'      => false,
            ])


            ################################################
            ####              Relations                 ####
            ################################################

            ->add('legendMonthlyReports', CollectionType::class, [
                'entry_type'    => MonthlyLegendListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ->add('legendEpcReports', CollectionType::class, [
                'entry_type'    => EpcLegendListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ->add('pvSystMonths', CollectionType::class, [
                'entry_type'    => PvSystMonthListEmbeddedFormType::class,
                'allow_add'     => false,
                'allow_delete'  => false,
                'delete_empty'  => true,
                'by_reference'  => false,
            ])
            ->add('monthlyYields', CollectionType::class, [
                'entry_type'    => MonthlyYieldListEmbeddedFormType::class,
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
        ;

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Anlage::class,
        ]);
    }
}

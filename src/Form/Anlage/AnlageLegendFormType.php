<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AnlageLegendFormType extends AbstractType
{
    private $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('anlName', TextType::class, [
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('projektNr', TextType::class, [
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('anlStrasse', TextType::class, [
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlPlz', TextType::class, [
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('anlOrt', TextType::class, [
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

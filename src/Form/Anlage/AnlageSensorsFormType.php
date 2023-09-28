<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Form\Sensors\SensorsListEmbeddedFormType;
use App\Helper\G4NTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class AnlageSensorsFormType extends AbstractType
{
    use G4NTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $builder
            // ###############################################
            // ###              Relations                 ####
            // ###############################################

            ->add('sensors', CollectionType::class, [
                'entry_type' => SensorsListEmbeddedFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])

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
            ->add('savecreatedb', SubmitType::class, [
                'label' => 'Save and Create Databases',
                'attr' => ['class' => 'secondary small', 'formnovalidate' => 'formnovalidate'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Anlage::class,
            'anlagenId' => '',
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}

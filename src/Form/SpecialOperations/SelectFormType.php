<?php

namespace App\Form\Reports;

use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('reportStatus', ChoiceType::class, [
                'label' => 'Status',
                'choices' => array_flip(self::reportStati()),//['final' => '0', 'under observation' => 3, 'proof reading' => '5', 'archive (only g4n)' => '9', 'draft (only g4n)' => '10', 'wrong (only g4n)' => '11'],
                'empty_data' => '0',
            ])
            ->add('comments', CKEditorType::class, [
                'config' => ['toolbar' => 'my_toolbar'],
                'empty_data' => '',
            ])
            ->add('headline', TextType::class, [
                'label' => 'Headline',
                'empty_data'=> '',
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Save',
                'attr' => ['class' => 'primary save'],
            ])
           ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([

        ]);
    }
}

<?php

namespace App\Form\Reports;

use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportsFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AnlagenReports $report */
        $report = $options['data'] ?? null;
        $isEdit = $report && $report->getId();

        $builder
            ->add('reportStatus', ChoiceType::class, [
                'label' => 'Status',
                'choices' => array_flip(self::reportStati()),//['final' => '0', 'under observation' => 3, 'proof reading' => '5', 'archive (only g4n)' => '9', 'draft (only g4n)' => '10', 'wrong (only g4n)' => '11'],
                'empty_data' => '0',
            ])
            ->add('headline', TextType::class, [
                'label' => 'Headline',
                'empty_data'=> '',
            ])
            ->add('comments', TextareaType::class, [
                #'config' => ['toolbar' => 'my_toolbar'],
                'empty_data' => '',
            ])
/*
            ->add('save', SubmitType::class, [
                'label' => 'Save',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close',
                'attr' => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close without save',
                'attr' => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlagenReports::class,
        ]);
    }
}

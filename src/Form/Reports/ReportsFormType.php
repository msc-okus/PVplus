<?php

namespace App\Form\Reports;

use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportsFormType extends AbstractType
{
    use G4NTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AnlagenReports $report */
        $report = $options['data'] ?? null;
        $isEdit = $report && $report->getId();

        $builder
            ->add('reportStatus', ChoiceType::class, [
                'label'         => 'Status',
                'choices'       => ['final' => '0', 'proof reading' => '5', 'archive (only g4n)' => '9', 'draft (only g4n)' => '10', 'wrong (only g4n)' => '11'],
                'empty_data'    => '0',
            ])

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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlagenReports::class
        ]);
    }


}
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
                'choices' => array_flip(self::reportStati()),
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

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlagenReports::class,
        ]);
    }
}

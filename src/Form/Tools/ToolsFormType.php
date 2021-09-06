<?php

namespace App\Form\Tools;

use App\Entity\Anlage;
use App\Form\Model\ToolsModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolsFormType extends AbstractType
{
    private $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository)
    {
        $this->anlagenRepository = $anlagenRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('anlage', EntityType::class, [
                'label'         => 'please select a Plant',
                'class'         => Anlage::class,
                'choices'       => $this->anlagenRepository->findAllActive(),
                'choice_label'  => 'anlName',
            ])
            ->add('startDate', DateType::class, [
                'widget'    => 'single_text',
                'format'    => 'yyyy-MM-dd',
                'data'      => new \DateTime('now'),
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'data'      => new \DateTime('now'),
            ])
            ->add('function', ChoiceType::class, [
                'choices'       => [
                    //'Write weather data to database'    => 'weather',
                    'Expected (New)'                    => 'expected',
                    'Update availability'               => 'availability',
                    'Update PR'                         => 'pr',
                ],
                'placeholder'   => 'please Choose ...'
            ])

            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################

            ->add('calc', SubmitType::class, [
                'label' => 'Start calculation',
                'attr'  => ['class' => 'primary save'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close (do nothing)',
                'attr'  => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ToolsModel::class
        ]);
    }
}

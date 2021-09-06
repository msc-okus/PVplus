<?php


namespace App\Form\DownloadData;

use App\Entity\Anlage;
use App\Form\Model\DownloadDataModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadDataFormType extends AbstractType
{
    private $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository)
    {
        $this->anlagenRepository = $anlagenRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $today = (new \DateTime('now'))->format('Y-m-d');
        $builder
            ->add('anlage', EntityType::class, [
                'label'         => 'please select a Plant',
                'class'         => Anlage::class,
                'choices'       => $this->anlagenRepository->findAllActive(),
                'choice_label'  => 'anlName',
            ])
            ->add('startDate', DateType::class, [
                'widget'        => 'single_text',
                'format'        => 'yyyy-MM-dd',
                'data'          => new \DateTime('now'),
                'attr'          => ['max' => $today],
            ])
            ->add('endDate', DateType::class, [
                'widget'        => 'single_text',
                'format'        => 'yyyy-MM-dd',
                'data'          => new \DateTime('now'),
                'attr'          => ['max' => $today],
            ])
            ->add('data', ChoiceType::class, [
                'choices'       => [
                    'All Data'          => 'all',
                    'AC Data'           => 'ac',
                    'DC Data'           => 'dc',
                    'Irradiation'       => 'irr',
                    'Availability'      => 'avail',
                ],
                'placeholder'   => 'please Choose ...'
            ])
            ->add('intervall', ChoiceType::class, [
                'label'         => 'summiere Daten',
                'choices'       => [
                    'per 15 Minutes'    => '%d.%m.%Y %H:%i',
                    'per Day'           => '%d.%m.%Y',
                    //'per Week'          => '%v',
                    'per Month'         => '%m',
                    //'per Year'          => '%Y',
                ],
                'placeholder'   => 'please Choose ...'
            ])

            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################

            ->add('calc', SubmitType::class, [
                'label'     => 'Load data',
                'attr'      => ['class' => 'primary save'],
            ])
            ->add('close', SubmitType::class, [
                'label'     => 'Close (do nothing)',
                'attr'      => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DownloadDataModel::class,
        ]);
    }
}

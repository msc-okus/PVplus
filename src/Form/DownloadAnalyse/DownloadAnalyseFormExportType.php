<?php


namespace App\Form\DownloadAnalyse;

use App\Entity\Anlage;
use App\Form\Model\DownloadAnalyseModel;
use App\Form\Model\DownloadDataModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadAnalyseFormExportType extends AbstractType
{
    private $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository)
    {
        $this->anlagenRepository = $anlagenRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('anlageexport', EntityType::class, [
                'label'         => ' ',
                'class'         => Anlage::class,
                'choices'       => $this->anlagenRepository->findIdLike($options['anlagenid']),
                'choice_label'  => 'anlName',
            ])
            ->add('year', HiddenType::class, [
            ])
            ->add('month', HiddenType::class, [
            ])
            ->add('day', HiddenType::class, [
            ])
            ->add('documenttype', ChoiceType::class, [
                'label'         => 'select Documenttype',
                'choices'       => [
                    'PDF'    => 'pdf',
                    'Excel'           => 'excel',
                ],
                'required' => true,
                'placeholder'   => 'please Choose ...'
            ])
            ->setAction('/download/analyse/download/'.$options['anlagenid'])
            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################

            ->add('export', SubmitType::class, [
                'label'     => 'Download',
                'attr'      => ['class' => 'primary save'],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'anlagenid' => null,
        ));
    }

}

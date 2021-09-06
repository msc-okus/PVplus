<?php


namespace App\Form\DownloadAnalyse;

use App\Entity\Anlage;
use App\Form\Model\DownloadAnalyseModel;
use App\Form\Model\DownloadDataModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadAnalyseFormType extends AbstractType
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
                'required'      => true,
            ])
            ->add('years', ChoiceType::class, [
                'label'         => 'please select a Year',
                'choices'       => $this->getYears(),
                'placeholder'   => 'please Choose a year first ...'
            ])
            ->add('months', ChoiceType::class, [
                'label'         => 'please select a Month',
                'choices'       => $this->getMonths(),
                'placeholder'   => 'please Choose ...',
            ])
            ->add('days', ChoiceType::class, [
                'label'         => 'please select a Day',
                'choices'       => $this->getDays(),
                'placeholder'   => 'please Choose ...',
            ])

            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################

            ->add('calc', SubmitType::class, [
                'label'     => 'generate Analyse',
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
            'data_class' => DownloadAnalyseModel::class,
            'anlagenid' => DownloadAnalyseModel::class,
        ]);
    }

    public function getYears(){
        $years = range(date('Y'), 2016);
        $yearsfinal = [];

        for ($i = 0; $i < count($years); $i++) {
            $yearsfinal[$years[$i]] = $years[$i];
        }

        return $yearsfinal;
    }

    public function getMonths(){
        $formattedMonthArray  = array(
            "January" => "01", "February" => "02", "March" => "03", "April" => "04",
            "May" => "05", "June" => "06", "July" => "07", "August" => "08",
            "September" => "09", "October" => "10", "November" => "11", "December" => "12",
        );

        return $formattedMonthArray;
    }

    public function getDays(){
        $dayArray = range(1, 31);
        $daysfinal = [];

        for ($i = 0; $i < count($dayArray); $i++) {
            $daysfinal[$dayArray[$i]] = $dayArray[$i];
        }
        return $daysfinal;
    }
}

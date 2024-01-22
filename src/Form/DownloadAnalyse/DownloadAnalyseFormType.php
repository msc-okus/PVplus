<?php

namespace App\Form\DownloadAnalyse;

use App\Entity\Anlage;
use App\Form\Model\DownloadAnalyseModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class DownloadAnalyseFormType extends AbstractType
{
    public function __construct(private readonly AnlagenRepository $anlagenRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anlage', EntityType::class, [
                'label' => 'please select a Plant',
                'class' => Anlage::class,
                'choices' => $this->anlagenRepository->findAllActiveAndAllowed(),
                'choice_label' => 'anlName',
                'required' => true,
            ])
            ->add('years', ChoiceType::class, [
                'required' => true,
                'label' => 'please select a Year',
                'choices' => $this->getYears(),
             ])
            ->add('months', ChoiceType::class, [
                'label' => 'please select a Month',
                'choices' => $this->getMonths(),
                'placeholder' => 'please Choose ...',
            ])
            ->add('days', ChoiceType::class, [
                'label' => 'please select a Day',
                'choices' => $this->getDays(),
                'placeholder' => 'please Choose ...',
            ])

            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################

            ->add('calc', SubmitType::class, [
                'label' => 'generate Table',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close (do nothing)',
                'attr' => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DownloadAnalyseModel::class,
            'anlagenid' => DownloadAnalyseModel::class,
        ]);
    }

    public function getYears(): array
    {
        $years = range(date('Y'), 2016);
        $yearsfinal = ['please Choose a year first ...' => '0'];

        for ($i = 0; $i < count($years); ++$i) {
            $yearsfinal[$years[$i]] = $years[$i];
        }

        return $yearsfinal;
    }

    public function getMonths(): array
    {
        $formattedMonthArray = [
            'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
            'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
            'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12',
        ];

        return $formattedMonthArray;
    }

    public function getDays(): array
    {
        $dayArray = range(1, 31);
        $daysfinal = [];

        for ($i = 0; $i < count($dayArray); ++$i) {
            $daysfinal[$dayArray[$i]] = $dayArray[$i];
        }

        return $daysfinal;
    }
}

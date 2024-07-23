<?php

namespace App\Form\LiveReporting;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Entity\LiveReporting;

class LifeReportingMonthly extends AbstractType {

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository
    )
    {
    }
	public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //create select for plant
        $anlagen = $this->anlagenRepository->findAllActiveAndAllowed();

        //create select for year
        $startYear = 2020;
        $currentYear = date('Y');
        $yearArray = [];
        for($year = $startYear; $year <= $currentYear; $year++) {
            $yearArray[$year] = $year;
        }

        //create select for month
        $monthnameArray = [
            'January' => 1,
            'February' => 2,
            'March' => 3,
            'April' => 4,
            'May' => 5,
            'June' => 6,
            'July' => 7,
            'August' => 8,
            'September' => 9,
            'October' => 10,
            'November' => 11,
            'December' => 12
        ];

		$isBugReport = $options['isBugReport'];

		switch ($options['flow_step']) {
			case 1:
                $builder->add('anlage', EntityType::class, [
                    'label' => 'Please select a Plant',
                    'class' => Anlage::class,
                    'choices' => $anlagen,
                    'choice_label' => 'anlName',
                    'attr' => array('style' => 'width: 200px')
                ]);
                $builder->add('year', ChoiceType::class, [
                    'choices' => $yearArray,
                    'placeholder' => 'please Choose ...',
                    'required' => true,
                    'attr' => array('style' => 'width: 200px')
                ]);
				$builder->add('month', ChoiceType::class, [
                    'choices' => $monthnameArray,
                    'placeholder' => 'please Choose ...',
                    'required' => true,
                    'attr' => array('style' => 'width: 200px')
				]);
				break;
			case 2:
                $year = $options['year'];
                $month = $options['month'];
                $anlagename = $options['anlagename'];

                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                //create select for month
                $startdayArray = [];
                $enddayArray = [];
                for($i=1; $i <= $daysInMonth; $i++) {
                    $startdayArray[$i] = $i;
                }

                $builder->add('daysinmonth', HiddenType::class, [
                    'data' => $daysInMonth,
                ]);

                $builder->add('anlagename', HiddenType::class, [
                    'data' => $anlagename,
                ]);

                $builder->add('startday', ChoiceType::class, [
                    'choices' => $startdayArray,
                    'placeholder' => 'please Choose ...',
                    'required' => true,
                    'attr' => array('style' => 'width: 200px')
                ]);


                $builder->add('endday', ChoiceType::class, [
                    'choices' => $startdayArray,
                    'placeholder' => 'please Choose ...',
                    'required' => true,
                    'attr' => array('style' => 'width: 200px')
                ]);

				break;
			case 3:
				if ($isBugReport) {
					$builder->add('details', TextareaType::class);
				}
				break;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void
    {
		$resolver->setDefaults([
            'data_class' => LiveReporting::class,
            'month' => '',
            'year' => '',
            'anlagename' => ''
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getBlockPrefix() : string {
		return 'createLifeRepoting_Monthly';
	}

}

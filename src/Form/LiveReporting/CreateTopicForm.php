<?php

namespace App\Form\LiveReporting;

use App\Entity\Anlage;
use App\Form\Model\ImportToolsModel;
use App\Repository\AnlagenRepository;
use Knp\Bundle\PaginatorBundle\DependencyInjection\Configuration;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use App\Form\Type\TopicCategoryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Entity\Topic;
/**
 * @author Christian Raue <christian.raue@gmail.com>
 * @copyright 2013-2022 Christian Raue
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CreateTopicForm extends AbstractType {

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly Security          $security
    )
    {
    }
	public function buildForm(FormBuilderInterface $builder, array $options) {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        //create select for plant
        if ($this->security->isGranted('ROLE_G4N')) {
            $anlagen = $this->anlagenRepository->findAllActiveAndAllowed();
        } else {
            $eigner = $this?->security->getUser()?->getEigners()[0];
            $anlagen = $this->anlagenRepository->findSymfonyImportByEigner($eigner);
        }

        $anlagen_toShow = [];
        $i = 0;
        foreach ($anlagen as $anlage) {
            $anlagen_toShow[$i] = $anlage;
            $i++;
        }

        //create select for year
        $startYear = 2020;
        $currentYear = date('Y');
        $yearArray = [];
        for($startYear=$startYear; $startYear <= $currentYear; $startYear++) {
            $yearArray[$startYear] = $startYear;
        }

        //create select for month
        $monthArray = [];
        for($i=1; $i <= 12; $i++) {
            $monthArray[$i] = $i;
        }

		$isBugReport = $options['isBugReport'];


		switch ($options['flow_step']) {
			case 1:
                $builder->add('year', ChoiceType::class, [
                    'choices' => $yearArray,
                    'placeholder' => 'please Choose ...',
                    'required' => true,
                ]);
				$builder->add('month', ChoiceType::class, [
                    'choices' => $monthArray,
                    'placeholder' => 'please Choose ...',
                    'required' => true,
				]);
				$builder->add('anlage', EntityType::class, [
                    'label' => 'Please select a Plant',
                    'class' => Anlage::class,
                    'choices' => $anlagen_toShow,
                    'choice_label' => 'anlName',
                ]);

				break;
			case 2:
                $year = $options['year'];
                $month = $options['month'];

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

                $builder->add('issm', HiddenType::class, [
                    'data' => 1,
                ]);

                $builder->add('startday', ChoiceType::class, [
                    'choices' => $startdayArray,
                    'placeholder' => 'please Choose ...',
                    'required' => true
                ]);


                $builder->add('endday', ChoiceType::class, [
                    'choices' => $startdayArray,
                    'placeholder' => 'please Choose ...',
                    'required' => true
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
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
            'data_class' => Topic::class,
            'month' => '',
            'year' => '',
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getBlockPrefix() : string {
		return 'createTopic';
	}

}

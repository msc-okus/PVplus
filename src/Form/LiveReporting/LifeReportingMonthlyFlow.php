<?php

namespace App\Form\LiveReporting;

use App\Repository\AnlagenRepository;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;


class LifeReportingMonthlyFlow extends FormFlow {

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
    )
    {
    }

	protected function loadStepsConfig(): array
    {
		$formType = LifeReportingMonthly::class;

		return [
			[
				'label' => 'Choose Year. Month and Plant',
				'form_type' => $formType,
			],
			[
				'label' => 'Choose start and endday',
				'form_type' => $formType,
			],
			[
				'label' => 'bug_details',
				'form_type' => $formType,
				'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
					return $estimatedCurrentStepNumber > 1 && !$flow->getFormData()->isBugReport();
				},
			],
			[
				'label' => 'confirmation',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFormOptions($step, array $options = []) {
		$options = parent::getFormOptions($step, $options);
        if ($step === 2) {
            $formData = $this->retrieveStepData();

            $anlage = $this->anlagenRepository->findOneBy(['anlId' => $formData[1]['anlage']]);
            $anlageName = $anlage->getAnlName();
            $options['year'] = $formData[1]['year'];
            $options['month'] = $formData[1]['month'];
            $options['anlagename'] = $anlageName;
        }

		if ($step === 3) {
			$options['isBugReport'] = $this->getFormData()->isBugReport();
		}


		return $options;
	}

}

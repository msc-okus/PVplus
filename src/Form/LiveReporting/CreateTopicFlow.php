<?php

namespace App\Form\LiveReporting;

use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

/**
 * This flow uses one form type for the entire flow.
 *
 * @author Christian Raue <christian.raue@gmail.com>
 * @copyright 2013-2022 Christian Raue
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CreateTopicFlow extends FormFlow {

	/**
	 * {@inheritDoc}
	 */
	protected function loadStepsConfig() {
		$formType = CreateTopicForm::class;

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
            $formData = $this->getFormData();

            echo "<pre>";
            print_r($this->retrieveStepData());
            echo "<pre>";
            exit;
            #$options['year'] = $formData->year;
            #$options['month'] =$formData->month;
        }

		if ($step === 3) {
			$options['isBugReport'] = $this->getFormData()->isBugReport();
		}

		return $options;
	}

}

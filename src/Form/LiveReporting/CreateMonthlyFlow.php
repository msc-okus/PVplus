<?php
namespace App\Form\LiveReporting;

use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use App\Form\LiveReporting\CreateMonthlyForm;

class CreateMonthlyFlow extends FormFlow {

    protected function loadStepsConfig() {
        return [
            [
                'label' => 'Plant',
                'form_type' => CreateMonthlyForm::class,
            ],
            [
                'label' => 'Start Day',
                'form_type' => CreateMonthlyForm::class,
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $estimatedCurrentStepNumber > 1 && !$flow->getFormData()->canHaveEngine();
                },
            ],
            [
                'label' => 'confirmation',
            ],
        ];
    }

}
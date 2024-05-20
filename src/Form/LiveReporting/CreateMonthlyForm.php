<?php
namespace App\Form\LiveReporting;

use App\Entity\Anlage;
use App\Form\Model\ImportToolsModel;
use App\Repository\AnlagenRepository;
use App\Form\Type\DateString;
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
use Craue\FormFlowBundle\Form\FormFlow;

class CreateMonthlyForm extends AbstractType {
    public function __construct(

    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        switch ($options['flow_step']) {
            case 1:
                $validValues = [2, 4];
                $builder->add('chousePlant', ChoiceType::class, [
                    'choices' => array_combine($validValues, $validValues),
                    'placeholder' => '',
                ]);
                break;
            case 2:
                // This form type is not defined in the example.
                $builder->add('startDay', DateString::class, [
                    'placeholder' => '',
                ]);
                break;
        }
    }

    public function getBlockPrefix() {
        return 'createMonthly';
    }

}

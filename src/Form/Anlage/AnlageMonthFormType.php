<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Entity\AnlageMonth;
use App\Entity\Eigner;
use App\Entity\WeatherStation;
use App\Form\EventMail\EventMailListEmbeddedFormType;
use App\Form\Groups\GroupsListEmbeddedFormType;
use App\Form\GroupsAc\AcGroupsListEmbeddedFormType;
use App\Helper\G4NTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Security\Core\Security;

class AnlageMonthFormType extends AbstractType
{
    use G4NTrait;

    private $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $monthArray = [
            'January'   => '1',
            'February'  => '2',
            'March'     => '3',
            'April'     => '4',
            'May'       => '5',
            'June'      => '6',
            'July'      => '7',
            'August'    => '8',
            'September' => '9',
            'October'   => '10',
            'November'  => '11',
            'December'  => '12',
        ];

        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $builder
            ->add('month', ChoiceType::class, [
                'choices'       => $monthArray,
                'placeholder'   => 'Please Choose',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('irrUpper', TextType::class, [
                'required'  => false,
                'empty_data'    => '0.5',
            ])
            ->add('irrLower', TextType::class, [
                'required'  => false,
                'empty_data'    => '0.5',
            ])
            ->add('shadowLoss', TextType::class, [
                'required'  => false,
                'empty_data'    => '0',
            ])
        ;

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => AnlageMonth::class,
            'anlagenId'     => '',
        ]);
        //$resolver->setAllowedTypes('anlagenId', 'string');
    }
}
<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
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

class AnlageDcGroupsFormType extends AbstractType
{
    use G4NTrait;

    private $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $builder
            ################################################
            ####              Relations                 ####
            ################################################

            ->add('groups', CollectionType::class, [
                'entry_type'    => GroupsListEmbeddedFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
                'entry_options' => ['anlagenId' => $options['anlagenId']],
            ])
            ->add('anlageMonth', CollectionType::class, [
                'entry_type'    => AnlageMonthFormType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'by_reference'  => false,
                'entry_options' => ['anlagenId' => $options['anlagenId']],
            ])

            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################
            ->add('save', SubmitType::class, [
                'label' => 'Save Plant',
                'attr'  => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close Plant',
                'attr'  => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close without save',
                'attr'  => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
            ->add('savecreatedb', SubmitType::class, [
                'label'         => 'Save and Create Databases',
                'attr'  => ['class' => 'secondary small', 'formnovalidate' => 'formnovalidate'],
            ])
        ;

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => Anlage::class,
            'anlagenId'     => '',
        ]);
        $resolver->setAllowedTypes('anlagenId', 'string');
    }
}

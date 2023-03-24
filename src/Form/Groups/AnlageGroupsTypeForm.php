<?php

namespace App\Form\Groups;

use App\Entity\Anlage;
use App\Entity\AnlageGroups;
use App\Entity\WeatherStation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnlageGroupsTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dcGroup', IntegerType::class,[
                'label'=>'DC Group',
                'help'=>'dcGroup'
            ])
            ->add('dcGroupName', TextType::class,[
                'label'=>'Group Name (Real Name)',
                'help'=>'dcGroupName'
            ])
            ->add('acGroup', IntegerType::class,[
                'label'=>'AC Group',
                'help'=>'acGroup'
            ])
            ->add('unitFirst', IntegerType::class,[
                'label'=>'First unit (GAK, Inverter, ...)',
                'help'=>'unitFirst'
            ])
            ->add('unitLast', IntegerType::class,[
                'label'=>'Last unit (GAK, Inverter, ...)',
                'help'=>'unitLast'
            ])
            ->add('irrUpper', TextType::class,[
                'label'=>'Irr Upper',
                'help'=>'irrUpper'
            ])
            ->add('irrLower', TextType::class,[
                'label'=>'Irr Lower',
                'help'=>'irrLower'
            ])
            ->add('secureLoss', TextType::class,[
                'label'=>'Secure Loss [%]',
                'help'=>'secureLoss'
            ])
            ->add('factorAC', TextType::class,[
                'label'=>'DC -> AC [%]',
                'help'=>'factorAC'
            ])
            ->add('gridLoss', TextType::class,[
                'label'=>'Grid Loss [%]',
                'help'=>'gridLoss'
            ])
            ->add('limitAc', TextType::class,[
                'label'=>'Limit Inverter AC [kWh]',
                'help'=>'limitAc'
            ])
            ->add('gridLimitAc', TextType::class,[
                'label'=>'Limit Grid AC [kWh??]',
                'help'=>'gridLimitAc'
            ])
            ->add('importId', TextType::class, [
                'help' => '[importId] ID to select Inverter via Import Script (Example: VCOM ID)',
                'label' => 'Import ID (for import script)',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('anlage',EntityType::class, [
                'class'=> Anlage::class,
                'disabled'=> true,
                'label'=>'Anlage',
                'help'=>'anlage',
                'attr' => ['style' => 'display: none;'],
            ])
            ->add('weatherStation', EntityType::class, [
                'label' => 'Weatherstation',
                'help' => '[weatherStation]',
                'class' => WeatherStation::class,
                'choice_label' => function (WeatherStation $station) {return sprintf('%s - %s', $station->getDatabaseIdent(), $station->getLocation()); },
                'placeholder' => 'select a Weatherstation',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('modules', CollectionType::class, [
                'entry_type' => GroupModulFormType::class,
                'label'=>false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'entry_options' => ['anlage' => $options['anlage']],
            ])
            ->add('months', CollectionType::class, [
                'entry_type' => MonthsListEmbeddedFormType::class,
                'label'=>false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageGroups::class,
            'anlage'=>null
        ]);

    }
}

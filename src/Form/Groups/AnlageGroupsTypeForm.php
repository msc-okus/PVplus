<?php

namespace App\Form\Groups;

use App\Entity\Anlage;
use App\Entity\AnlageGroups;
use App\Entity\WeatherStation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
            ->add('shadowLoss', TextType::class,[
                'label'=>'Shadow Loss',
                'help'=>'shadowLoss'
            ])
            ->add('cabelLoss', TextType::class,[
                'label'=>'Cabel Loss',
                'help'=>'cabelLoss'
            ])
            ->add('secureLoss', TextType::class,[
                'label'=>'Secure Loss',
                'help'=>'secureLoss'
            ])
            ->add('factorAC', TextType::class,[
                'label'=>'DC -> AC[%]',
                'help'=>'factorAC'
            ])
            ->add('gridLoss', TextType::class,[
                'label'=>'Grid Loss',
                'help'=>'gridLoss'
            ])
            ->add('limitAc', TextType::class,[
                'label'=>'Abriegelung Inverter AC',
                'help'=>'limitAc'
            ])
            ->add('gridLimitAc', TextType::class,[
                'label'=>'Abriegelung Grid AC',
                'help'=>'gridLimitAc'
            ])
            ->add('anlage',EntityType::class, [
                'class'=> Anlage::class,
                'disabled'=> true,
                'label'=>'Anlage',
                'help'=>'anlage'
            ])
            ->add('weatherStation', EntityType::class, [
                'class'=> WeatherStation::class,
                'choice_label'=>function(WeatherStation $weatherStation){
                    return $weatherStation->getLocation();
                },
                'disabled'=> true,
                'label'=>'WeatherStation',
                'help'=>'weatherStation'

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageGroups::class,
        ]);
    }
}

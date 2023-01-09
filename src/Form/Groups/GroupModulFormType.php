<?php

namespace App\Form\Groups;

use App\Entity\AnlageGroupModules;
use App\Entity\AnlageModules;
use App\Repository\ModulesRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupModulFormType extends AbstractType
{


    public function __construct(private ModulesRepository $modulesRepository)
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*$choices=[];
        if ($options['anlage']){
            $choices = $this->modulesRepository->findByAnlage($options['anlage']);
        }*/

        $builder
            ->add('moduleType', EntityType::class, [
                'class' => AnlageModules::class,
                'choice_label' => 'type',
              /*  'choices'=>$choices*/

            ])
            ->add('numStringsPerUnit', IntegerType::class, [
                'empty_data' => 0,
            ])
            ->add('numStringsPerUnitEast', IntegerType::class, [
                'required' => false,
                'empty_data' => 0,
            ])
            ->add('numStringsPerUnitWest', IntegerType::class, [
                'required' => false,
                'empty_data' => 0,
            ])
            ->add('numModulesPerString', IntegerType::class, [
                'empty_data' => 0,
                'attr'=>[
                    'readonly'=>true
                ]

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageGroupModules::class,
           /* 'anlage'=>null*/
        ]);

    }
}

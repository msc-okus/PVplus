<?php

namespace App\Form\Groups;

use App\Entity\Anlage;
use App\Entity\AnlageGroups;
use App\Repository\AnlagenRepository;
use App\Repository\GroupsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class DcGroupsSearchFormType extends AbstractType
{

    public function __construct(private GroupsRepository $groupsRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anlage',EntityType::class, [
                'placeholder'=> 'choose a Plant',
                'empty_data'=>'',
                'label'=>false,
                'class'=> Anlage::class,
                'choice_label'=>function(Anlage $anlage){
                    return $anlage->getAnlName();
                },
                'query_builder' => fn(AnlagenRepository $anlagenRepository)
                => $anlagenRepository-> findAllOrderedByAscNameQueryBuilder(),
                'attr'=>[
                    'onchange'=>'this.form.submit()'
                ]
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

        ]);
    }
}

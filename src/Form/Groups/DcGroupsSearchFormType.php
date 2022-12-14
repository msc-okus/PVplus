<?php

namespace App\Form\Groups;

use App\Entity\Anlage;
use App\Entity\AnlageGroups;
use App\Entity\Country;
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

    public function __construct(private  GroupsRepository $groupsRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anlage',EntityType::class, [
                'placeholder'=> 'choose a Plant',
                'attr'=>[
                    'data-groups-target'=>"anlage",
                    'data-action'=>"change->groups#sortedByAnlage"
                ],
                'label'=>false,
                'required'=>false,
                'class'=> Anlage::class,
                'choice_label'=>function(Anlage $anlage){
                    return $anlage->getAnlName();
                },
                'query_builder' => fn(AnlagenRepository $anlagenRepository)
                => $anlagenRepository-> findAllOrderedByAscNameQueryBuilder()
            ])
            ->add('dcGroup',EntityType::class, [
                'placeholder'=> 'choose a DcGroup',

                'attr'=>[
                    'data-groups-target'=>"dcgroup"

                ],
                'label'=>false,
                'class'=> AnlageGroups::class,
                'choice_label'=>function(AnlageGroups $anlageGroups){
                    return $anlageGroups->getAnlage()?'DcGrp->'.$anlageGroups->getDcGroupName().'- Anlage->'.$anlageGroups->getAnlage()->getAnlName():'DcGrp->'.$anlageGroups->getDcGroupName().'- Anlage->UNDEFINED ';
                },
                'query_builder' => fn(GroupsRepository $groupsRepository)
                => $groupsRepository-> findAllOrderedByAscNameQueryBuilder(),
                'constraints'=> new NotBlank(['message' => 'Please choose a DcGroup'])
            ])
        ;

        $formModifier = function(FormInterface $form, Anlage $anlage=null){
            $dcGroups=$anlage === null? $this->groupsRepository->findAll():$anlage->getGroups();

            $form->add('dcGroup',EntityType::class, [
                'placeholder'=> 'choose a DcGroup',
                'attr'=>[
                    'data-groups-target'=>"dcgroup"

                ],
                'label'=>false,
                'class'=> AnlageGroups::class,
                'choice_label'=>function(AnlageGroups $anlageGroups){
                    return $anlageGroups->getAnlage()?'DcGrp->'.$anlageGroups->getDcGroupName().'- Anlage->'.$anlageGroups->getAnlage()->getAnlName():'DcGrp->'.$anlageGroups->getDcGroupName().'- Anlage->UNDEFINED ';
                },
                'choices'=>$dcGroups,
                'constraints'=> new NotBlank(['message' => 'Please choose a DcGroup'])
            ]);
        };

        $builder->get('anlage')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use($formModifier){
               $anlage=$event->getForm()->getData();
               $formModifier($event->getForm()->getParent(),$anlage);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

        ]);
    }
}

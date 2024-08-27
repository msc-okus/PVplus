<?php

namespace App\Form\Groups;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DcGroupsSearchFormType extends AbstractType
{

    public function __construct()
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
                'choice_label'=>fn(Anlage $anlage) => $anlage->getAnlName(),
                'query_builder' => fn(AnlagenRepository $anlagenRepository)
                => $anlagenRepository->querBuilderFindAllActiveAndAllowed(),
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

<?php

namespace App\Form\DownloadAnalyse;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadAnalyseFormExportType extends AbstractType
{
    public function __construct(private readonly AnlagenRepository $anlagenRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anlageexport', EntityType::class, [
                'label' => ' ',
                'class' => Anlage::class,
                'choices' => $this->anlagenRepository->findIdLike($options['anlagenid']),
                'choice_label' => 'anlName',
            ])
            ->add('year', HiddenType::class, [
            ])
            ->add('month', HiddenType::class, [
            ])
            ->add('day', HiddenType::class, [
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'anlagenid' => null,
            'required' => false,
        ]);
    }
}

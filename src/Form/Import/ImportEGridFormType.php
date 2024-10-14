<?php

namespace App\Form\Import;

use App\Entity\Anlage;
use App\Form\Model\ToolsModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;


class ImportEGridFormType extends AbstractType
{
    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly Security          $security
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $anlagen = $this->anlagenRepository->findAllActiveAndAllowed();

        $builder
            ->add('anlage', EntityType::class, [
                'label' => 'Please select a Plant',
                'class' => Anlage::class,
                'placeholder' => 'Please select a Plant',
                'choices' => $anlagen,
                'choice_label' => 'anlName',
                'autocomplete' => true
            ])
            ->add('file', FileType::class, [
                'mapped' => false,
                'label' => 'eGrid File (xlsx)',
                'constraints' => [
                    new File([
                        'maxSize' => '10Mi',
                        'extensions' => ['xlsx'],
                    ]),
                ],
            ])


            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################

            ->add('calc', SubmitType::class, [
                'label' => 'Start calculation',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close (do nothing)',
                'attr' => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ]);
    }

##
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ToolsModel::class,
            'required' => false
        ]);
    }
}

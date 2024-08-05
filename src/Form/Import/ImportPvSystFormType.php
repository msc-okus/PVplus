<?php

namespace App\Form\Import;

use App\Entity\Anlage;

use App\Form\Model\ImportPvSystModel;
use App\Form\Model\ToolsModel;

use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\File;


class ImportPvSystFormType extends AbstractType
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
            ])
            ->add('file', FileType::class, [
                'mapped' => false,
                'label' => 'PV Syst File (csv)',
                'constraints' => [
                    new File([
                        'maxSize' => '10Mi',
                        #'extensions' => ['csv','txt'],
                    ]),
                ],
            ])
            ->add('separator', ChoiceType::class, [
                'choices' => [';' => ';', ',' => ',']
            ])
            ->add('dateFormat', ChoiceType::class, [
                'choices'   => [
                    'DD/MM/YY hh:mm' => 'd/m/y H:i',
                    'MM/DD/YY hh:mm' => 'm/d/y H:i',
                ]
            ])
            ->add('filename', TextType::class, [
                'attr' => [
                    'readonly' => 'readonly',
                ]
            ])



            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################
            ->add('preview', SubmitType::class, [
                'label' => 'Preview File',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('import', SubmitType::class, [
                'label' => 'Start Import',
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
            'data_class' => ImportPvSystModel::class,
            'required' => false,
        ]);
    }
}

<?php

namespace App\Form\FileUpload;

use App\Repository\AnlagenRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class FileUploadFormType extends AbstractType
{
    private $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository)
    {
        $this->anlagenRepository = $anlagenRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                    ]),
                ],
            ])
            ->add('File', FileType::class, [
                'mapped' => false,
                'label' => 'CSV File',
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
               //             'application/csv'
                        ],
                    ]),
                ],
            ])

            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################

            ->add('import', SubmitType::class, [
                'label' => 'Upload',
                'attr' => ['class' => 'primary save'],
            ]);
    }
}

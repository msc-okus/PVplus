<?php

namespace App\Form\FileUpload;

use App\Repository\AnlagenRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
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
                        'maxSize' => '5M'
                    ])
                ]
            ])

            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################

            ->add('export', SubmitType::class, [
                'label'     => 'Upload',
                'attr'      => ['class' => 'primary save'],
            ]);

    }


}

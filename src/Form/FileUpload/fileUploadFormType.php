<?php


namespace App\Form\FileUpload;

use App\Entity\Anlage;
use App\Form\Model\DownloadAnalyseModel;
use App\Form\Model\DownloadDataModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class fileUploadFormType extends AbstractType
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

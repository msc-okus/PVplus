<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use App\Form\EventMail\EventMailListEmbeddedFormType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\UX\Dropzone\Form\DropzoneType;

class MultiUploadFormType extends AbstractType
{

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('files', FileType::class, [
                'label' => ' ',
                'multiple' => 'multiple',
                'mapped'      => false
            ])
            ->add('save', SubmitType::class, [
                'label' => 'upload File(s)',
                'attr' => ['class' => 'primary save'],
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {

    }
}
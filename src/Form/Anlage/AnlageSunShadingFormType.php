<?php
namespace App\Form\Anlage;

use App\Entity\AnlageSunShading;
use App\Form\Type\SwitchType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class AnlageSunShadingFormType extends AbstractType {
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct( private Security $security )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $anlage = $builder->getData();
        if (!$anlage instanceof AnlageSunShading) {
            throw new \RuntimeException('Invalid entity.');
        }

        $builder
            // ###############################################
            // ###          SunShading                    ####
            // ###############################################
            ->add('mod_height', TextType::class, [
                'label' => 'Modul Height',
                'help' => '[Module Height in mm , example 300]',
                'label_html' => true,
                'empty_data' => '0',
                'attr' => ['pattern' => '[0-9]{2}', 'maxlength' => 2]
            ])
        ;
    }
}
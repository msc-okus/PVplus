<?php

namespace App\Form\Anlage;

use App\Entity\AnlageModules;
use App\Entity\AnlageModulesDB;
use App\Entity\AnlageSunShading;
use App\Repository\AnlageModulesDBRepository;
use App\Repository\AnlageSunShadingRepository;
use App\Repository\ModulesRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;

/**
 * MS 08/2023
 * SunShadingListEmbeddedForm
 */

class SunShadingListEmbeddedFormType extends AbstractType
{
    public function __construct(
        private ModulesRepository $modulesRepository,
        private AnlageModulesDBRepository $anlageModulesDBRepository,
        private AnlageSunShadingRepository $anlageSunShadingRepository,
        private EntityManagerInterface $em
    )
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
           $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description for this',
                'help' => '[Enter an Description for this model !]',
                'empty_data' => 'default shading model',
                'label_html' => true,
                'required' => true,
            ])
            ->add('mod_height', TextType::class, [
                'label' => 'The module height',
                'empty_data' => '0',
                'help' => '[The module height in mm]',
                'label_html' => true,
                'attr' => ['maxlength' => 4],
                'required' => true,
            ])
            ->add('mod_width', TextType::class, [
                'label' => 'The module width',
                'empty_data' => '0',
                'help' => '[The module width in mm]',
                'label_html' => true,
                'attr' => ['maxlength' => 4],
                'required' => true,
            ])
            ->add('modulesDB', EntityType::class, [
                'label' => 'Select the modul',
                'help' => '[Select the modul of tables]',
                'placeholder' => 'Please Choose',
                'class' => AnlageModulesDB::class,
                'choice_label' => 'type',
                'expanded' => false,
                'multiple' => false,
                'mapped' => true,
            ])
            ->add('mod_tilt', TextType::class, [
                'label' => 'The modul tilt',
                'empty_data' => '0',
                'help' => '[The modul tilt in mm]',
                'label_html' => true,
                'attr' => ['maxlength' => 4],
                'required' => true,
            ])
            ->add('mod_table_height', TextType::class, [
                'label' => 'The table height',
                'empty_data' => '0',
                'help' => '[The table height in mm]',
                'label_html' => true,
                'attr' => ['maxlength' => 4],
                'required' => true,
            ])
            ->add('mod_table_distance', TextType::class, [
                'label' => 'The table distance',
                'empty_data' => '0',
                'help' => '[The distance in mm]',
                'label_html' => true,
                'attr' => ['maxlength' => 4],
                'required' => true,
            ])
            ->add('distance_a', TextType::class, [
                'label' => 'The distance of A',
                'empty_data' => '0',
                'help' => '[The distance of A in mm]',
                'label_html' => true,
                'attr' => ['maxlength' => 4],
                'required' => true,
            ])
            ->add('distance_b', TextType::class, [
                'label' => 'The distance of B',
                'help' => '[The distance of B in mm]',
                'empty_data' => '0',
                'label_html' => true,
                'attr' => ['maxlength' => 4],
                'required' => true,
            ])
            ->add('ground_slope', TextType::class, [
                'label' => 'Have a ground slope',
                'empty_data' => '0',
                'help' => '[The ground slope in %]',
                'label_html' => true,
                'attr' => ['maxlength' => 2],
                'required' => true,
            ])
        ;
    }

    public function getModules() {
        $conn = $this->em->getConnection();
        $query = "SELECT `type`, `id` FROM `anlage_modules` order by `type`";
        $stmt = $conn->executeQuery($query);
        return $stmt->fetchAllKeyValue();
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageSunShading::class,
        ]);
    }

}

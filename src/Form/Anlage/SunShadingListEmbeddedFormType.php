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
                'help' => '[Enter an description for this sunshading model !]',
                'empty_data' => 'default shading model',
                'label_html' => true,
                'required' => true,
            ])

            /*->add('mod_height', TextType::class, [
                'label' => 'The module height',
                'empty_data' => '0',
                'help' => '[The module height in mm]',
                'label_html' => true,
                'attr' => ['maxlength' => 4],
                'required' => true,
            ])*/

            ->add('mod_width', TextType::class, [
                'label' => 'The light width [LW]',
                'empty_data' => '0',
                'help' => 'The light width between behind edge row 1 to behind edge row 2 [LW] in mm',
                'label_html' => true,
                'attr' => ['pattern' => '[0-9]{4}', 'maxlength' => 4],
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
                'label' => 'The modul tilt [ß]',
                'empty_data' => '0',
                'help' => '[The modul tilt [ß] in °]',
                'label_html' => true,
                'attr' => ['pattern' => '[0-9]{4}', 'maxlength' => 4],
                'required' => true,
            ])
            ->add('mod_table_height', TextType::class, [
                'label' => 'The table height [M]',
                'empty_data' => '0',
                'help' => '[The table height [M] in mm]',
                'label_html' => true,
                'attr' => ['pattern' => '[0-9]{4}', 'maxlength' => 4],
                'required' => true,
            ])
            ->add('mod_table_distance', TextType::class, [
                'label' => 'Row division [RT]',
                'empty_data' => '0',
                'help' => '[Row division distance behind edge to next behind edge in mm]',
                'label_html' => true,
                'attr' => ['pattern' => '[0-9]{4}', 'maxlength' => 4],
                'required' => true,
            ])
            ->add('distance_a', TextType::class, [
                'label' => 'The Lot [H]',
                'empty_data' => '0',
                'help' => '[The Lot from table top to footpoint [H] in mm]',
                'label_html' => true,
                'attr' => ['pattern' => '[0-9]{4}', 'maxlength' => 4],
                'required' => true,
            ])
            ->add('distance_b', TextType::class, [
                'label' => 'Distance under the table [d]',
                'help' => '[Distance under the table from [M] to Lot [h] in mm]',
                'empty_data' => '0',
                'label_html' => true,
                'attr' => ['pattern' => '[0-9]{4}', 'maxlength' => 4],
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

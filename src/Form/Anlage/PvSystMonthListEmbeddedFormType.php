<?php


namespace App\Form\Anlage;

use App\Entity\AnlageLegendReport;
use App\Entity\AnlagenPvSystMonth;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PvSystMonthListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('month', ChoiceType::class, [
                'choices'       => array_combine(range(1,12), range(1,12)),
                'placeholder'   => 'please choose'
            ])
            ->add('prDesign', TextType::class, [
                'label'         => 'PR Design',
                'empty_data'    => 0,
                'required'      => false,
            ])
            ->add('ertragDesign', TextType::class, [
                'label'         => 'Yield Design',
                'empty_data'    => '',
                'required'      => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlagenPvSystMonth::class,
        ]);
    }
}
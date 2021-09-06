<?php


namespace App\Form\Anlage;

use App\Entity\AnlageLegendReport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EpcLegendListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', HiddenType::class, [
                'data'      => 'epc',
            ])
            ->add('row', TextType::class, [
                'label'         => 'Row',
                'empty_data'    => '',
                'required'      => true,
            ])
            ->add('title', TextType::class, [
                'label'         => 'Title / Formula',
                'empty_data'    => '',
                'required'      => true,
            ])->add('unit', TextType::class, [
                'label'         => 'Unit',
                'empty_data'    => '',
                'required'      => false,
            ])->add('description', TextareaType::class, [
                'label'         => 'Description',
                'empty_data'    => '',
                'required'      => false,
            ])
            ->add('source', TextType::class, [
                'label'         => 'Description',
                'empty_data'    => '',
                'required'      => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageLegendReport::class,
        ]);
    }
}
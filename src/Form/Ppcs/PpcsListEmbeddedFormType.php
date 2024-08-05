<?php

namespace App\Form\Ppcs;

use App\Entity\AnlagePpcs;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PpcsListEmbeddedFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vcomId', TextType::class, [
                'required' => false,
            ])
            ->add('startDatePpc', DateTimeType::class, [

                'widget' => 'single_text',
                'required'      => false,
                'by_reference' => true,
            ])
            ->add('endDatePpc', DateTimeType::class, [
                'widget' => 'single_text',
                'required'      => false,
                'by_reference' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlagePpcs::class,
        ]);
    }
}

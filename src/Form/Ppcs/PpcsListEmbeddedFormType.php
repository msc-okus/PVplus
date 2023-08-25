<?php

namespace App\Form\Ppcs;

use App\Entity\AnlagePpcs;
use App\Form\Type\SwitchType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class PpcsListEmbeddedFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlagePpcs::class,
        ]);
    }
}

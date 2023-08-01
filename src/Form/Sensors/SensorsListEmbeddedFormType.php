<?php

namespace App\Form\Sensors;

use App\Entity\AnlageSensors;
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

class SensorsListEmbeddedFormType extends AbstractType
{
    use G4NTrait;
    use PVPNameArraysTrait;
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nameShort', TextType::class, [

            ])
            ->add('name', TextType::class, [

            ])
            ->add('type', ChoiceType::class, [
                'choices'       => self::sensorTypes(),
                'placeholder'   => 'please Select'

            ])
            ->add('virtualSensor', ChoiceType::class, [
                'choices'       => self::virtualSensors(),
                'placeholder'   => 'please Select'

            ])
            ->add('useToCalc', SwitchType::class, [
                'required' => false,
            ])
            ->add('orientation', ChoiceType::class, [
                'choices'       => self::sensorOriantation(),
                'placeholder'   => 'please Select',
                'required'      => false,
            ])
            ->add('vcomId', TextType::class, [
                'required' => false,
            ])
            ->add('vcomAbbr', TextType::class, [
                'required' => false,
            ])
            ->add('startDateSensor', DateTimeType::class, [

                'widget' => 'single_text',
                'required'      => false,
                'by_reference' => true,
            ])
            ->add('endDateSensor', DateTimeType::class, [
                'widget' => 'single_text',
                'required'      => false,
                'by_reference' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageSensors::class,
        ]);
    }
}

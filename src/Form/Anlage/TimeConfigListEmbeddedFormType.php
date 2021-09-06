<?php


namespace App\Form\Anlage;

use App\Entity\TimesConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeConfigListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [

                'choices'   => ['Availability' => 'availability_first', 'Availability Second' => 'availability_second'],
                'required'  => true
            ])
            ->add('startDateMonth', ChoiceType::class, [
                'choices'   => array_combine(range(1,12), range(1,12)),
                'placeholder'   => 'please choose'
            ])
            ->add('startDateDay', ChoiceType::class, [
                'choices'   => array_combine(range(1,31), range(1,31)),
                'placeholder'   => 'please choose'
            ])
            ->add('endDateMonth', ChoiceType::class, [
                'choices'   => array_combine(range(1,12), range(1,12)),
                'placeholder'   => 'please choose'
            ])
            ->add('endDateDay', ChoiceType::class, [
                'choices'   => array_combine(range(1,31), range(1,31)),
                'placeholder'   => 'please choose'
            ])
            ->add('startTime', TimeType::class)
            ->add('endTime', TimeType::class)
            ->add('maxFailTime', TextType::class, [
                'empty_data'    => '30',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TimesConfig::class,
        ]);
    }
}
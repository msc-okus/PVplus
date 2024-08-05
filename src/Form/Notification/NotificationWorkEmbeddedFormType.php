<?php


namespace App\Form\Notification;

use App\Entity\NotificationWork;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationWorkEmbeddedFormType extends AbstractType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options = [
            "Begin Work"    => 10,
            "Pause"         => 20,
            "Finished Job"  => 30
        ];
        $builder
            ->add('begin', DateTimeType::class, [
                'label' => 'Time',
                'label_html' => true,
                'required' => true,
                'widget' => 'single_text',
            ])
        ->add("type", ChoiceType::class,[
            'label' => 'Interval Type',
            'choices' =>$options,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NotificationWork::class,
        ]);
    }
}
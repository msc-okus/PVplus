<?php


namespace App\Form\Notification;

use App\Entity\ContactInfo;
use App\Entity\Eigner;
use App\Entity\NotificationWork;
use App\Entity\OwnerFeatures;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NotificationWork::class,
        ]);
    }
}
<?php


namespace App\Form\Notification;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class NotificationEditFormType extends AbstractType
{
    public function __construct(
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $answers=[
          'I will do it'                    => 20,
          'I cannot do it at the moment'    => 30,
          'I cannot do it at all'           => 40
        ];
        $builder->add('answers', ChoiceType::class, [
            'label'     => 'Select an answer',
            'placeholder' => 'Select an answer',
            'required'  => true,
            'choices'   => $answers

        ])
        ->add('freeText', TextareaType::class,[
            'label' => 'Your message',
            'required'   => false,
            'empty_data' => '',
            'attr' => ['rows' => '9'],
        ])
        ->add("answer", SubmitType::class,[
            'label' => 'Send your answer <i class="fa fa-paper-plane"></i>',
            'label_html' => true,
            'attr' => ['class' => 'primary save'],
        ]);

    }
}
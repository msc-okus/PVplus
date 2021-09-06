<?php


namespace App\Form\EventMail;

use App\Entity\AnlageEventMail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventMailListEmbeddedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('event', ChoiceType::class, [
                'choices'   => ['Alert' => 'alert', 'Montly Report' => 'monthlyReport'],
                'required'  => true,
            ])
            ->add('mail', TextType::class, [
                'required'  => true,

            ])
            ->add('firstName')
            ->add('lastName')
            ->add('sendType',ChoiceType::class, [
                'choices'   => ['to'=>'to', 'cc'=>'cc', 'bcc'=>'bcc'],
                'required'  => true,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnlageEventMail::class,
        ]);
    }
}
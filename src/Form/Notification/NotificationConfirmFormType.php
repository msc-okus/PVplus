<?php


namespace App\Form\Notification;

use App\Entity\ContactInfo;
use App\Entity\Eigner;
use App\Entity\OwnerFeatures;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationConfirmFormType extends AbstractType
{
    public function __construct(
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $answers=[
            'Issue fixed'                                                           => 50,
            'There was an unexpected problem and the problem could not be fixed'    => 60,

        ];
        $builder->add('answers', ChoiceType::class, [
            'label'     => 'Select an answer',
            'choices'   => $answers

        ])
            ->add('freeText', TextareaType::class,[
                'label' => 'Free Text',
                'attr' => ['rows' => '9'],
                'required'   => false,
            ])
            ->add("answer", SubmitType::class,[
                'label' => 'Answer',
                'attr' => ['class' => 'primary save'],
            ]);

    }
}
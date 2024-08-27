<?php


namespace App\Form\Notification;

use App\Entity\NotificationInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            'Please select an option'                                               => 30,
            'Issue fixed'                                                           => 50,
            'There was an unexpected problem and the problem could not be fixed'    => 60,

        ];
        $builder->add('status', ChoiceType::class, [
            'label'     => 'Select an answer',

            'required'  => true,
            'choices'   => $answers,
            'empty_data' => 30

        ])
        ->add('closeFreeText', TextareaType::class,[
            'label' => 'Close Message',
            'empty_data' => '',
            'attr' => ['rows' => '9'],
            'required'   => false,
        ])
        ->add("answer", SubmitType::class,[
            'label' => 'Submit <i class="fa fa-paper-plane"></i>',
            'label_html' => true,
            'attr' => ['class' => 'primary save'],
        ])
        ->add('notificationWorks', CollectionType::class, [
        'entry_type' => NotificationWorkEmbeddedFormType::class,
        'allow_add' => true, //This should do the trick.
    ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NotificationInfo::class,

        ]);
    }
}
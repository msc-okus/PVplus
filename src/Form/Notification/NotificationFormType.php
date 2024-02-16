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

class NotificationFormType extends AbstractType
{
    public function __construct(
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $contacts = $options['eigner']->getContactInfos();
        $choices = [];
        foreach ($contacts as $contact){
            $choices[$contact->getCompanyName() . " - " . $contact->getName() . " - " . $contact->getService()] = $contact->getId();
        }
        $builder->add('contacted', ChoiceType::class, [
            'label'     => 'Select a contact',
            'choices'   => $choices
        ])
        ->add('freeText', TextareaType::class,[
            'label' => 'Free Text',
            'empty_data' => '',
            'attr' => ['rows' => '9'],
        ])
        ->add("contact", SubmitType::class,[
            'label' => 'Send',
            'attr' => ['class' => 'primary save'],
        ]);

    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'eigner' => null,
        ));
    }
}

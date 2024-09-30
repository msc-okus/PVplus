<?php

namespace App\Form\Owner;

use App\Entity\ApiConfig;
use App\Entity\ContactInfo;
use App\Entity\OwnerFeatures;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Helper\PVPNameArraysTrait;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class OwnerApiFormType extends AbstractType
{
    use PVPNameArraysTrait;
    public function __construct(
    )
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('apiType', ChoiceType::class, [
            'choices' => self::apiTypes(),
            'placeholder' => 'please Select ...',
            'required' => true,
            'attr' => array('style' => 'width: 200px')
        ])
        ->add('configName', TextType::class, [
            'label'  => 'Name of the Config'
        ])
        ->add('apiUser', TextType::class, [
            'label' => 'API User',
            'help'  => '[API User]'
        ])
        ->add('apiPassword', TextType::class, [
            'label' => 'API Passwort',
            'help'  => '[API Password]'

        ])
        ->add('apiToken', TextType::class, [
            'label' => 'API Token',
            'help'  => '[mcToken]'

        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApiConfig::class,
        ]);
    }
}
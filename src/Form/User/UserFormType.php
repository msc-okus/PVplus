<?php

namespace App\Form\User;

use App\Entity\Eigner;
use App\Entity\User;
use Doctrine\DBAL\Types\BooleanType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /** @var User $user */
        $user = $options['data'] ?? null;
        $isEdit = $user && $user->getUserId();

        $builder
            ->add('username', TextType::class, [
                'label'             => 'User Name',
                'required'          => true,
                'attr'              => ['placeholder' => 'Benutzername'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'              => PasswordType::class,
                'invalid_message'   => 'The password fields must match.',
                'first_options'     => ['label' => 'Password'],
                'second_options'    => ['label' => 'Repeat Password'],
                'required'          => true,
                'mapped'            => false,
                'data'              => '',
            ])
            ->add('email', EmailType::class, [
                'label'         => 'eMail address',
                'empty_data'    => ''
            ])
            ->add('level', TextType::class, [
                'label'         => 'User Level',
                'empty_data'    => 1,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => User::ARRAY_OF_ROLES,
                'multiple'      => true,
                'expanded'      => true,
                'attr'          => ['class' => 'callout']
            ])
            ->add('eigners', EntityType::class, [
                'class'         => Eigner::class,
                'multiple'      => true,
                'expanded'      => true,
                'choice_label'  => 'firma',
                'by_reference'  => false,
            ])
            ->add('grantedList', TextType::class, [
                'label'         => 'List with IDs of granted facilities',
                'empty_data'    => '',
            ])


            ##############################################
            ####          STEUERELEMENTE              ####
            ##############################################
            ->add('save', SubmitType::class, [
                'label'     => 'Save Owner',
                'attr'      => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label'     => 'Save and Close Owner',
                'attr'      => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label'     => 'Close without save',
                'attr'      => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
<?php

namespace App\Form\User;

use App\Controller\SecurityController;
use App\Entity\Eigner;
use App\Entity\User;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;


class UserFormType extends AbstractType
{
    private AnlagenRepository $repo;

    public function __construct(AnlagenRepository $repo,private Security $security)
    {
        $this->repo = $repo;

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        /** @var Eigner $eigner */

        $user = $options['data'] ?? null;
        $isEdit = $user && $user->getUserId();
        $anlagen = [];

        $eigner = $this?->security?->getUser()?->getEigners()[0];
        $anlagen = $this?->repo?->findAllIDByEigner($eigner);

        if ($user != null) {
            $GrantedArray = $user?->getGrantedArray();
         }

        if ($GrantedArray) {
            foreach ($GrantedArray as $Gkey) {
                $fixGrantedArray[] = preg_replace('/\s+/', '', $Gkey);
            }
        }

        if ($anlagen){
            foreach ($anlagen as $key => $val){
                $anlagenid[] = [$anlagen[$key]['anlName'] => $anlagen[$key]['anlId']];
            }
        }

       if ($this->security->isGranted('ROLE_ADMIN_USER') and $this->security->getUser()->getUsername() != "admin"){
           $choicesRolesArray = User::ARRAY_OF_ROLES_USER;
       } else {
           $choicesRolesArray = User::ARRAY_OF_ROLES;
       }

           $singlechoince = [$eigner?->getFirma() => $eigner?->getId()];



        $builder
            ->add('username', TextType::class, [
                'label' => 'User Name',
                'required' => true,
                'attr' => ['placeholder' => 'Benutzername'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
                'required' => true,
                'mapped' => false,
                'data' => '',
            ])
            ->add('email', EmailType::class, [
                'label' => 'eMail address',
                'empty_data' => '',
            ])
            ->add('level', TextType::class, [
                'label' => 'User Level',
                'empty_data' => 1,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $choicesRolesArray,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('eigners', EntityType::class, [
                'class' => Eigner::class,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'firma',
                'by_reference' => false,
            ])

            ->add('singleeigners', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => true,
                'choices' => $singlechoince,
                'data' => $singlechoince,
                'disabled' => true,
            ])

            ->add('grantedList', TextType::class, [
                'label' => '',
                'compound' => true,
                'empty_data' => '',
            ])
            ->add('eignersPlantList', ChoiceType::class,[
                'choices' => $anlagenid,
                'expanded' => true,
                'multiple' => true,
                'required' => true,
                'data' => $fixGrantedArray,
                'mapped' => false
            ])

            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################
            ->add('save', SubmitType::class, [
                'label' => 'Save User',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('saveclose', SubmitType::class, [
                'label' => 'Save and Close User',
                'attr' => ['class' => 'primary saveclose'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close without save',
                'attr' => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ])
            ->add('delete', SubmitType::class, [
                'label' => 'Delete User',
                'attr' => ['class' => 'primary delete'],
            ])
           ;
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

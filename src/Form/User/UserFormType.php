<?php

namespace App\Form\User;

use App\Controller\SecurityController;
use App\Entity\Eigner;
use App\Entity\User;
use App\Repository\AnlagenRepository;
use App\Repository\EignerRepository;
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


    public function __construct(private AnlagenRepository $anlagenRepo, private EignerRepository $ownerRepo, private Security $security)
    {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        /** @var Eigner $eigner */

        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin     = $this->security->isGranted('ROLE_ADMIN');
        $isG4N       = $this->security->isGranted('ROLE_G4N');

        $user = $options['data'] ?? null;
        $isEdit = $user && $user->getUserId();

        $anlagen = $ownerArray = [];

        //Find Lsit of all Owners (Eigners)
        if ($this->security->isGranted('ROLE_G4N')) {
            foreach ($this->ownerRepo->findAll() as $eigner) {
                $ownerArray[$eigner->getFirma()] = $eigner->getId();
            }
        } else {
            $ownerArray[$this->security->getUser()->getEigners()[0]->getFirma()] = $this->security->getUser()->getEigners()[0]->getId();
        }
        // Find current Owner ID (Eigners ID)
        if ($isEdit) {
            if ($user->getEigners()->count() > 0) {
                $userId = $user->getEigners()[0]->getId();
            } else {
                $userId = 1; //User ID = 1 == G4N
            }

        } else {
            if ($this->security->isGranted('ROLE_G4N')) {
                $userId = 0;
            } else {
                $userId = $this->security->getUser()->getEigners()[0]->getId();
            }
        }
        // Find Owner related Plants
        $anlagen = $this->anlagenRepo->findAllIDByEigner($userId);


        if ($user != null) {
            $GrantedArray = $user->getGrantedArray();
         }
        if (isset($GrantedArray)) {
            foreach ($GrantedArray as $Gkey) {
                $fixGrantedArray[] = preg_replace('/\s+/', '', $Gkey);
            }
        }
        if ($anlagen){
            foreach ($anlagen as $key => $anlage){
                $anlagenid[] = [$anlage['anlName'] => $anlage['anlId']];
            }
        }

        if ($this->security->isGranted('ROLE_ADMIN_USER') and $this->security->getUser()->getUsername() != "admin"){
           $choicesRolesArray = User::ARRAY_OF_ROLES_USER;
        } else {
           $choicesRolesArray = User::ARRAY_OF_ROLES;
        }


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
            ->add('eigners', ChoiceType::class, [
                'label' => 'Owner',
                'choices' => $ownerArray,
                'disabled' => (!$isAdmin) || ($isEdit),
                'data'  => $userId,
                'mapped' => false,
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

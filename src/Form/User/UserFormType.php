<?php

namespace App\Form\User;

use App\Controller\SecurityController;
use App\Entity\Eigner;
use App\Entity\Anlage;
use App\Entity\User;
use App\Repository\AnlagenRepository;
use App\Repository\EignerRepository;
use App\Repository\UserRepository;
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
use Symfony\Component\Security\Core\Security;


class UserFormType extends AbstractType
{

    public function __construct(
        private AnlagenRepository $anlagenRepo,
        private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        /** @var Eigner $eigner_l */

        $user = $options['data'] ?? null;
        $isEdit = $user && $user->getUserId();
        $anlagen = [];
        //the logged eigner id
        if ($this->security->isGranted('ROLE_G4N')){
            $eigner = null;
        }else {
            $eigner = $this?->security->getUser()?->getEigners()[0];
        }
        //find selected eigner id
        $sel_eigner = $user?->getEigners()[0];

         if (!$sel_eigner) {
             $anlagen = $this->anlagenRepo->findAllIDByEigner($eigner);
         } else {
             $anlagen = $this->anlagenRepo->findAllIDByEigner($sel_eigner);
         }

        if ($user != null) {
            $grantedArray = $user->getGrantedArray();
            if ($grantedArray) {
                foreach ($grantedArray as $Gkey) {
                    $fixGrantedArray[] = preg_replace('/\s+/', '', $Gkey);
                }
            }
        }

        if ($anlagen){
            foreach ($anlagen as $key => $anlage){
                $anlagenid[] = [$anlage['anlName'] => $anlage['anlId']];
            }
        }

       if ($this->security->isGranted('ROLE_ADMIN_USER') and $this->security->getUser()->getUsername() != "admin"){
           $choicesRolesArray = array_merge(User::ARRAY_OF_ROLES_USER, User::ARRAY_OF_FUNCTIONS_BY_ROLE);
          } else {
           $choicesRolesArray = array_merge($choicesRolesArray = User::ARRAY_OF_ROLES, User::ARRAY_OF_FUNCTIONS_BY_ROLE);
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
                'required' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $choicesRolesArray,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('eigners', EntityType::class, [
                'class' => Eigner::class,
                'attr' => array('class' =>'type_label'),
                'multiple' => true,
                'expanded' => true,
                'required' => true,
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
                'mapped' => false,
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'plant_'.strtolower($value)];
                },
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
            ]) ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

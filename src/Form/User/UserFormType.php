<?php

namespace App\Form\User;

use App\Entity\Eigner;
use App\Entity\User;
use App\Form\Type\SwitchType;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class UserFormType extends AbstractType
{

    public function __construct(
        private readonly AnlagenRepository $anlagenRepo,
        private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        /** @var Eigner $eigner_l */

        $user = $options['data'] ?? null;
        // Wenn User 'gelocked' ist dann setze $disabled auf true
        $readonly = $user && $user->getLocked() ;

        //the logged eigner id
        if ($this->security->isGranted('ROLE_G4N')){
            $eigner = null;
        } else {
            $eigner = $this->security->getUser()->getEigners()[0];
        }
        //find selected eigner id
        $sel_eigner = $user?->getEigners()[0];

         if (!$sel_eigner) {
             $anlagen = $this->anlagenRepo->findAllIDByEigner($eigner);
         } else {
             $anlagen = $this->anlagenRepo->findAllIDByEigner($sel_eigner);
         }

        if ($user !== null) {
            $grantedArray = $user->getGrantedArray(false);
        } else {
            $grantedArray = [];
        }
        $anlagenid = [];
        if ($anlagen){
            foreach ($anlagen as $anlage){
                $anlagenid[] = [strtoupper((string) $anlage['country'])." | ".$anlage['anlName'] => $anlage['anlId']];
            }
        }

       if ($this->security->isGranted('ROLE_G4N')){
           $choicesRolesArray = [...User::ARRAY_OF_G4N_ROLES, ...User::ARRAY_OF_ROLES_USER, ...User::ARRAY_OF_FUNCTIONS_BY_ROLE];
       } else {
           $choicesRolesArray = [...User::ARRAY_OF_ROLES_USER, ...$this->security->getUser()->getRolesArrayByFeature()];
       }

       $singlechoince = [$eigner?->getFirma() => $eigner?->getId()];

        $builder
            ->add('username', TextType::class, [
                'label' => 'User Name',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Benutzername',
                    'readonly' => $readonly,
                ],
            ])
            ->add('newPlainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'readonly' => $readonly,
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'eMail address',
                'empty_data' => '',
                'required' => true, 'attr' => [
                    'readonly' => $readonly,
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $choicesRolesArray,
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'readonly' => $readonly,
                ],
            ])
            ->add('eigners', EntityType::class, [
                'class' => Eigner::class,
                'attr' => ['class' =>'type_label'],
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
            ->add('allPlants', SwitchType::class, [
                'label'         => 'allow all activ Plants',
                'required'      => false,
            ])
            ->add('grantedList', TextType::class, [
                'label' => '',
                'compound' => true,
                'empty_data' => '',
            ])
            ->add('use2fa', SwitchType::class, [
                'label'         => 'use 2 factor Authentification',
                'required'      => false,
            ])
            ->add('eignersPlantList', ChoiceType::class,[
                'choices' => $anlagenid,
                'expanded' => true,
                'multiple' => true,
                'required' => true,
                'data' => $grantedArray,
                'mapped' => false,
                'choice_attr' => fn($choice, $key, $value) => ['class' => 'plant_'.strtolower((string) $value)],
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

}

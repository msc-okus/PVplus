<?php

namespace App\Form\Anlage;

use App\Entity\Anlage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnlageStringAssigmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $anlageWithAssignments = $options['anlageWithAssignments'];

        $builder
            ->add('anlage', EntityType::class, [
                'class' => Anlage::class,
                'choice_label' => function (Anlage $anlage) use ($anlageWithAssignments) {
                    $lastUploadDate = $anlage->getLastAnlageStringAssigmentUpload();
                    $dateStr = $lastUploadDate ? $lastUploadDate->format('d-m-Y H:i:s') : ' never';
                    $hasAssignments = isset($anlageWithAssignments[$anlage->getAnlId()]);
                    $arrow = $hasAssignments ? '🔵' : '';
                    return sprintf("%s (%s) - Last upload: %s  %s", $anlage->getAnlName(),$anlage->getAnlId(), $dateStr, $arrow);
                },
                'label' => 'Select Anlage:',
                'attr' => [
                    'class' => 'custom-select',
                ],

            ])
            ->add('file', FileType::class, [
                'label' => 'Upload File:',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'anlageWithAssignments' => null,
            'required' => false,
        ]);
    }
}

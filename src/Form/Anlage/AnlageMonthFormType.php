<?php

namespace App\Form\Anlage;

use App\Entity\AnlageMonth;
use App\Helper\G4NTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class AnlageMonthFormType extends AbstractType
{
    use G4NTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $monthArray = [
            'January' => '1',
            'February' => '2',
            'March' => '3',
            'April' => '4',
            'May' => '5',
            'June' => '6',
            'July' => '7',
            'August' => '8',
            'September' => '9',
            'October' => '10',
            'November' => '11',
            'December' => '12',
        ];

        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $builder
            ->add('month', ChoiceType::class, [
                'choices' => $monthArray,
                'placeholder' => 'Please Choose',
                'empty_data' => '',
                'required' => true,
            ])
            ->add('irrUpper', TextType::class, [
                'required' => false,
                'empty_data' => '0.5',
            ])
            ->add('irrLower', TextType::class, [
                'required' => false,
                'empty_data' => '0.5',
            ])
            ->add('shadowLoss', TextType::class, [
                'required' => false,
                'empty_data' => '0',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnlageMonth::class,
            'anlagenId' => '',
            'required' => false,
        ]);
        // $resolver->setAllowedTypes('anlagenId', 'string');
    }
}

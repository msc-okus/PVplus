<?php
namespace App\Form\Anlage;

use App\Entity\AnlageSunShading;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AnlageSunShadingFormType extends AbstractType {
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct( private readonly Security $security )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $anlage = $builder->getData();
        if (!$anlage instanceof AnlageSunShading) {
            throw new \RuntimeException('Invalid entity.');
        }

        $builder
            // ###############################################
            // ###          SunShading                    ####
            // ###############################################
            /*>add('mod_height', TextType::class, [
                'label' => 'Modul Height',
                'help' => '[Module Height in mm , example 300]',
                'label_html' => true,
                'empty_data' => '0',
                'attr' => ['pattern' => '[0-9]{2}', 'maxlength' => 2]
            ])*/
        ;
    }
}
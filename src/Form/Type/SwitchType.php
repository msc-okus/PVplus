<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class SwitchType extends CheckboxType
{


    public function getBlockPrefix()
{
    return "switch_type";
}
}
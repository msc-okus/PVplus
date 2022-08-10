<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class SwitchType extends CheckboxType
{
    public function getBlockPrefix(): string
    {
        return 'switch_type';
    }
}

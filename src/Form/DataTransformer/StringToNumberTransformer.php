<?php

namespace App\Form\DataTransformer;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StringToNumberTransformer implements DataTransformerInterface
{
    public function __construct()
    {
    }

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        $value = (String)$value;
        if($value == null) return 0;
        dump($value);
        if($value != null) {
            dump("entro");
            $value = str_replace(',', '.', $value);
            $value = str_replace(' ', '', $value);
        }
        dump($value);
        return (float)$value;
    }

}

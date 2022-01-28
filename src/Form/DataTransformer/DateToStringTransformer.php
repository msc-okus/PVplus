<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateToStringTransformer implements DataTransformerInterface
{

    public function __construct()
    {

    }
    public function transform($value)
    {
        $dates = date('d.m.y H:i', strtotime($value));
        $date = date_create_from_format('d.m.y H:i', $dates);
        return ($date);
    }

    public function reverseTransform($value)
    {
        return$value->format('d.m.y H:i');
    }
}
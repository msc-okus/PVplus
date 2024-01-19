<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class DateToStringTransformer implements DataTransformerInterface
{
    public function __construct()
    {
    }

    public function transform($value): mixed
    {
        $dates = date('d.m.y H:i', strtotime((string) $value));
        $date = date_create_from_format('d.m.y H:i', $dates);

        return $date;
    }

    public function reverseTransform($value): mixed
    {
        return $value->format('d.m.y H:i');
    }
}

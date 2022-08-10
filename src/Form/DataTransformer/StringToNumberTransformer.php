<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class StringToNumberTransformer implements DataTransformerInterface
{
    public function __construct()
    {
    }

    public function transform($value): mixed
    {
        return $value;
    }

    public function reverseTransform($value): mixed
    {
        $value = (string) $value;
        if ($value === null) {
            return 0;
        } else {
            $value = str_replace(',', '.', $value);
            $value = str_replace(' ', '', $value);
        }

        return (float) $value;
    }
}

<?php

namespace App\Form\DataTransformer;

use App\Repository\AnlagenRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class NameToAnlageTransformer implements DataTransformerInterface
{
    private AnlagenRepository $anlnRepo;

    public function __construct(AnlagenRepository $anlRepo)
    {
        $this->anlnRepo = $anlRepo;
    }

    public function transform($value): mixed
    {
        if ($value === null) {
            $Anl = '';
        } else {
            $Anl = $value->getAnlName();
        }

        return $Anl;
    }

    public function reverseTransform($value): mixed
    {
        $Anlage = $this->anlnRepo->findOneBy(['anlName' => $value]);
        if (!$Anlage) {
            throw new TransformationFailedException(sprintf('No plant found by that name'));
        }

        return $Anlage;
    }
}

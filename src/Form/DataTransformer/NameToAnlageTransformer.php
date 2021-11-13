<?php

namespace App\Form\DataTransformer;

use App\Entity\Anlage;
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
    public function transform($value)
    {
        return $value->getAnlName();
    }

    public function reverseTransform($value)
    {
        dump($value);
        $Anlage = $this->anlnRepo->findOneBy(['anlName' => $value]);
        if (!$Anlage) { throw new TransformationFailedException(sprintf('No plant found by that name'));}

        return $Anlage;
    }

}
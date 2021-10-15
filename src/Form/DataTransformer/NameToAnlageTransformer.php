<?php

namespace App\Form\DataTransformer;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class NameToAnlageTransformer implements DataTransformerInterface
{
    private $anlnRepo;
    public function __construct(AnlagenRepository $anlRepo)
    {
        $this->anlnRepo = $anlRepo;
    }

    public function transform($value)
    {
        if($value === null) return '';
     //   if(!value instanceof Anlage) throw new \LogicException('this method can only be used with Anlage type');
        return $value->getAnlagenId();
    }

    public function reverseTransform($value)
    {
        $Anlage = $this->anlnRepo->findOneBy(['anlName' => $value]);
        if(!$Anlage) throw new TransformationFailedException(sprintf('No plant found by that name'));
        else return $Anlage;
    }

}
<?php

namespace App\Entity;

use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

#[Deprecated]
class Case6Array
{
    private array $Case6s;

    public function __construct()
    {
    }

    public function getCase6s()
    {
        return $this->Case6s;
    }

    public function setCase6s(array $Case6s): self
    {
        $this->Case6s = $Case6s;

        return $this;
    }
}

<?php

namespace App\Twig;

use App\Repository\PVSystDatenRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PvSystExtension extends AbstractExtension
{
    private $pvSystRepo;

    public function __construct(PVSystDatenRepository $pvSystRepo)
    {
        $this->pvSystRepo = $pvSystRepo;
    }
    /*
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
            new TwigFilter('filter_name', [$this, 'doSomething']),
        ];
    }
*/
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pvSystDayResult', [$this, 'pvSystDayResult']),
        ];
    }

    public function pvSystDayResult($anlage, $stamp)
    {
        $result = $this->pvSystRepo->sumByStamp($anlage, $stamp->format('Y-m-d'));

        return $result;
    }

}

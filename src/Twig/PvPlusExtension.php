<?php

namespace App\Twig;

use App\Repository\PVSystDatenRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PvPlusExtension extends AbstractExtension
{


    public function __construct()
    {
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
            new TwigFunction('getJson', [$this, 'getJson']),
        ];
    }


    public function getJson($json)
    {
        return json_decode($json);
    }
}

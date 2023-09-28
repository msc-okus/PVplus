<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ProcessCheckerExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('check_process', $this->checkProcess(...)),
        ];
    }

    public function checkProcess($command): string
    {
        $command = '';
        exec('ps -A comm,pid', $command, $retval);

        if (1) {
            $return = "'$command' is running ($retval)";
        } else {
            $return = "'$command' is NOT running ($help)";
        }

        return $return;
    }
}

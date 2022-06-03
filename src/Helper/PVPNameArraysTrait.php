<?php

namespace App\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

trait PVPNameArraysTrait
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public static function timeArray(): array
    {
        return  [
            '+5'    => '5',
            '+4'    => '4',
            '+3.75' => '3.75',
            '+3.50' => '3.50',
            '+3.25' => '3.25',
            '+3'    => '3',
            '+2.75' => '2.75',
            '+2.50' => '2.50',
            '+2.25' => '2.25',
            '+2'    => '2',
            '+1.75' => '1.75',
            '+1.50' => '1.50',
            '+1.25' => '1.25',
            '+1'    => '1',
            '+0.75' => '0.75',
            '+0.50' => '0.50',
            '+0.25' => '0.25',
            '0'    => '0',
            '-0.25' => '-0.25',
            '-0.50' => '-0.50',
            '-0.75' => '-0.75',
            '-1'    => '-1',
            '-1.25' => '-1.25',
            '-1.50' => '-1.50',
            '-1.75' => '-1.75',
            '-2'    => '-2',
            '-2.25' => '-2.25',
            '-2.50' => '-2.50',
            '-2.75' => '-2.75',
            '-3'    => '-3',
            '-3.25' => '-3.25',
            '-3.50' => '-3.50',
            '-3.75' => '-3.75',
            '-4'    => '-4',
            '-5'    => '-5',
        ];
    }

    public static function reportStati(): array
    {
        // Values for Report Status
        $reportStati[0]  = 'final';
        $reportStati[5]  = 'proof reading';
        $reportStati[9]  = 'archive (g4n only)';
        $reportStati[10] = 'draft (g4n only)';
        $reportStati[11] = 'wrong (g4n only)';

        return $reportStati;
    }

    public function ticketStati(): array
    {
        $status[$this->translator->trans('ticket.status.10')] = 10;
        $status[$this->translator->trans('ticket.status.20')] = 20;
        $status[$this->translator->trans('ticket.status.30')] = 30;
        $status[$this->translator->trans('ticket.status.40')] = 40;

        $status[$this->translator->trans('ticket.status.90')] = 90;

        return $status;
    }
}


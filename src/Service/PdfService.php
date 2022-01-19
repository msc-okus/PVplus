<?php

namespace App\Service;

use App\Entity\Anlage;
use Nuzkito\ChromePdf\ChromePdf;

class PdfService
{
    private string $tempPathBaseUrl;

    public function __construct(string $tempPathBaseUrl)
    {
        $this->tempPathBaseUrl = $tempPathBaseUrl;
    }

    /**
     * Create PDF File and store the PDF in a temp. file
     *
     * @param Anlage $anlage
     * @param string $html contains html or filename or url
     * @param string $source choose from wich source (given html, file or url)
     * @return string
     */
    public function createPdfTemp(Anlage $anlage, string $html, string $source = 'string'): string
    {
        $pdf = new ChromePdf('/usr/bin/chromium');
        $fullfilename = $this->tempPathBaseUrl.'/'.$anlage->getAnlName().'_tempPDF.pdf';

        $pdf->output($fullfilename);
        switch ($source) {
            case 'string':
                $pdf->generateFromHtml($html);
                break;
            case 'file':
                $pdf->generateFromFile($html);
                break;
            case 'url':
                $pdf->generateFromUrl($html);
                break;
        }

        $filename = $anlage->getAnlName().'_tempPDF.pdf';
        $pdf->output($filename);

        return $this->tempPathBaseUrl.'/'.$filename;
    }
}
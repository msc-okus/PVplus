<?php

namespace App\Service;

use App\Entity\Anlage;
use chromeheadlessio\Service;
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
     * @param string|null $source choose from wich source (given html, file or url)
     * @return string
     */
    public function createPdfTemp(Anlage $anlage, string $html, ?string $source = null, $tempPathBase = ""): string
    {
        if ($source === null) {
            //Create ChromeHeadless service with your token key specified
            $secretToken = '2bf7e9e8c86aa136b2e0e7a34d5c9bc2f4a5f83291a5c79f5a8c63a3c1227da9';
            $service = new Service($secretToken);
            //Get PDF generated from html content and push it to browser
            $service->export([
                "html"=>$html,
                "waitUntil"=>"domcontentloaded",
            ])->pdf([
                "format"=>"A4",
                "orientation"=>"landscape",
                "printBackground"=>true,
            ])->sendToBrowser("helloworld.pdf");

            return '';
        } else {
            $pdf = new ChromePdf('/usr/bin/chromium');

            $fullfilename = $this->tempPathBaseUrl.'/'.$anlage->getAnlName().'_tempPDF';
            #$fullfilename = $tempPathBase.'/'.$anlage->getAnlName().'_tempPDF';
            $filename = $anlage->getAnlName().'_tempPDF.pdf';

            $pdf->output($fullfilename.'.pdf');
            switch ($source) {
                case 'string':
                    $pdf->generateFromHtml($html);
                    break;
                case 'file':
                    #$pdf->generateFromFile($html);
                    break;
                case 'url':
                    #$pdf->generateFromUrl($html);
                    break;
            }

            $pdf->output($filename);

            return $this->tempPathBaseUrl.'/'.$filename;
        }
    }
}
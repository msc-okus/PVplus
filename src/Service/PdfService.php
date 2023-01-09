<?php

namespace App\Service;

use App\Entity\Anlage;
use chromeheadlessio\Service;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Nuzkito\ChromePdf\ChromePdf;

class PdfService
{

    public function __construct(
        private $tempPathBaseUrl,
        private Pdf $snappyPdf,
    )
    {
    }

    /**
     * Create PDF File and store the PDF in a temp. file.
     *
     * @param string      $html   contains html or filename or url
     * @param string|null $source choose from wich source (given html, file or url)
     */
    public function createPdfTemp(Anlage $anlage, string $html, ?string $source = null, $filename = 'tempPDF.pdf'): string|PdfResponse
    {
        if ($source === null) {
            // Create ChromeHeadless service with your token key specified
            $secretToken = '2bf7e9e8c86aa136b2e0e7a34d5c9bc2f4a5f83291a5c79f5a8c63a3c1227da9';
            $service = new Service($secretToken);
            // Get PDF generated from html content and push it to browser
            $service->export([
                'html' => $html,
                'waitUntil' => 'domcontentloaded',
            ])->pdf([
                'format' => 'A4',
                'orientation' => 'landscape',
                'printBackground' => true,
            ])->sendToBrowser($filename.'.pdf');

            return '';
        } else {
            switch ($source) {
                case 'string':
                    #$pdf->generateFromHtml($html);
                    return new PdfResponse(
                        $this->snappyPdf->getOutputFromHtml(
                            $html, ['enable-local-file-access' => true, 'orientation' => 'landscape']),
                        $filename
                    );
                case 'file':
                    // $pdf->generateFromFile($html);
                    break;
                case 'url':
                    // $pdf->generateFromUrl($html);
                    break;
            }

            return $this->tempPathBaseUrl.'/'.$filename;
        }
    }
}

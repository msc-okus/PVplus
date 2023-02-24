<?php

namespace App\Service;

use chromeheadlessio\Service;
use Knp\Snappy\Pdf;

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
    public function createPdf(string $html, ?string $source = null, string $filename = 'tempPDF.pdf', string $orientation = 'landscape'): void
    {
        $pdf = "";
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
        } else {
            switch ($source) {
                case 'string':
                    $pdf = $this->snappyPdf->getOutputFromHtml($html, [
                        'enable-local-file-access' => true,
                        'orientation'   => "$orientation",
                        'page-size'     => 'A4',
                        'margin-top'    => '5',
                        'margin-right'  => '10',
                        'margin-bottom' => '5',
                        'margin-left'   => '10',
                        'print-media-type'  => true,
                        'disable-smart-shrinking' => true,
                    ]);
                    break;
                case 'file':
                case 'url':
                    $pdf = $this->snappyPdf->getOutput($html, ['enable-local-file-access' => true, 'load-error-handling' => 'ignore', 'orientation' => "$orientation"]);
                    break;
            }
            $tempPdf = tmpfile();

            fwrite($tempPdf, $pdf);
            fseek($tempPdf,0);
            header("Content-Disposition: attachment; filename=" . urlencode($filename));
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header("Content-Description: File Transfer");
            header("Content-Length: " . filesize(stream_get_meta_data($tempPdf)['uri']));

            while (!feof($tempPdf))
            {
                echo fread($tempPdf, 65536);
                flush(); // this is essential for large downloads
            }


            fclose($tempPdf);
        }

        return;
    }
}

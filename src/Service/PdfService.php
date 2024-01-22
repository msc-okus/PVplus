<?php

namespace App\Service;

use chromeheadlessio\Service;
use Knp\Snappy\Pdf;
use League\Flysystem\Filesystem;

class PdfService
{

    public function __construct(
        private readonly Pdf $snappyPdf,
        private readonly Filesystem $fileSystemFtp,
    )
    {
    }

    /**
     * Create PDF File and store the PDF in a temp. file.
     *
     * @param string      $html   contains html or filename or url
     * @param string|null $source choose from wich source (given html, file or url)
     */
    public function createPdf(string $html, ?string $source = null, string $filename = 'tempPDF.pdf', string $orientation = 'landscape', bool $store = false): void
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

    /**
     * We use this to create the page of a Pdf that we will store in the db and later join to make the final pdf
     *
     * @param string $html contains html or filename or url
     * @param $name
     * @return string this contains the route of the file that should be stored in the db
     */
    public function createPage(string $html, string $fileroute, $name, bool $view, string $orientation = 'landscape'): string
    {
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
        $filepath = './pdf/' . $fileroute . '/' . $name . '.pdf';
        $filepath = str_replace(" ", "_", $filepath);
        $fileroute = './pdf/' . $fileroute;
        $fileroute = str_replace(" ", "_", $fileroute);
       // if ($this->fileSystemFtp->fileExists($fileroute) === false)$this->fileSystemFtp->createDirectory( $fileroute );
        $this->fileSystemFtp->write($filepath, $pdf);
        if ($view) {
           $resource = $this->fileSystemFtp->readStream($filepath);
           fseek($resource, 0);
           header("Content-Disposition: attachment; filename=" . urlencode($fileroute . '/' . $name . '.pdf'));
           header("Content-Type: application/force-download");
           header("Content-Type: application/octet-stream");
           header("Content-Type: application/download");
           header("Content-Description: File Transfer");
           header("Content-Length: " . filesize(stream_get_meta_data($resource)['uri']));

           while (!feof($resource)) {
               echo fread($resource, 65536);
               flush(); // this is essential for large downloads
           }
        }
        return $filepath;
    }



}

<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class UploaderHelper
{
    public const PLANT_IMAGE = 'plants';
    public const PLANT_REFERENCE = 'plant_reference';
    public const EIGNER_LOGO = 'eigners';
    public const CSV = 'csv';

    public function __construct(
        private readonly string     $tempPathBaseUrl,
        private Filesystem $fileSystemFtp,
        private RequestStackContext $requestStackContext
    )

    {
    }

    /**
     * @throws FilesystemException
     */
    public function uploadImage(UploadedFile $uploadedFile, $id, string $type): array
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $mimeType = pathinfo($uploadedFile->getClientMimeType(), PATHINFO_FILENAME);

        $newFilename = Urlizer::urlize($originalFilename).'-'.uniqid().'.'.$uploadedFile->guessExtension();
        switch ($type) {
            case 'plant':
                $foldern = self::PLANT_IMAGE.'/';
                break;
            case 'owner':
                $foldern = self::EIGNER_LOGO.'/';
                break;
            case 'reference':
            $foldern = self::PLANT_REFERENCE.'/';
            break;
            case 'csv':
            $foldern = self::CSV.'/';
            break;
            default:
                $foldern = '/';
        }

        $this->fileSystemFtp->write(
            $foldern.$id.'/'.$newFilename,
            file_get_contents($uploadedFile->getPathname())
        );

        $result = [
            'mimeType' => $mimeType,
            'newFilename' => $newFilename,
            'path' => $foldern.$id.'/'.$newFilename,
        ];

        return $result;
    }


    public function uploadImageSFTP(UploadedFile $uploadedFile, $owner, $anlage,  string $type): array
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $mimeType = pathinfo($uploadedFile->getClientMimeType(), PATHINFO_FILENAME);

        $newFilename = Urlizer::urlize($originalFilename).'-'.uniqid().'.'.$uploadedFile->guessExtension();
        switch ($type) {
            case 'plant':
                $fileroute = './images/'.$owner.'/'.$anlage.'/'.self::PLANT_IMAGE.'/';
                break;
            case 'owner':
                $fileroute = './images/'.$owner.'/'.self::EIGNER_LOGO.'/';
                break;
            case 'other':
                $fileroute = './images/'.$owner.'/'.$anlage.'/others/';
        }
        $fileroute = str_replace(" ", "_", $fileroute);
        if ($this->fileSystemFtp->fileExists($fileroute) === false)$this->fileSystemFtp->createDirectory( $fileroute );
        $this->fileSystemFtp->write(
            $fileroute.$newFilename,
            file_get_contents($uploadedFile->getPathname())
        );
        $result = [
            'mimeType' => $mimeType,
            'newFilename' => $newFilename,
            'path' => $fileroute.$newFilename,
        ];

        return $result;
    }
    /**
     * @throws \Exception
     */
    public function uploadArticleReference(File $file): string
    {
        return $this->uploadFile($file, self::PLANT_REFERENCE, false);
    }

    public function getPublicPath(string $path): string
    {
        $fullPath = $this->tempPathBaseUrl.'/'.$path;
        echo $fullPath.'<br>';
        // if it's already absolute, just return
        if (strpos($fullPath, '://') !== false) {
            return $fullPath;
        }

        // needed if you deploy under a subdirectory
        return $this->requestStackContext
            ->getBasePath().$fullPath;
    }

    /**
     * @return resource
     * @throws FilesystemException
     */
    public function readStream(string $path)
    {
        $resource = $this->fileSystemFtp->readStream($path);

        if ($resource === false) {
            throw new \Exception(sprintf('Error opening stream for "%s"', $path));
        }

        return $resource;
    }

    public function deleteFile(string $path): void
    {
        $this->fileSystemFtp->delete($path);
    }

    public function uploadFile(File $file, string $directory, bool $isPublic): string
    {
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }
        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();

        $stream = fopen($file->getPathname(), 'r');
        $this->fileSystemFtp->writeStream(
            $directory.'/'.$newFilename,
            $stream,
            [
                'visibility' => $isPublic ? Visibility::PUBLIC : Visibility::PRIVATE,
            ]
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $newFilename;
    }

    public function uploadAllFile(File $file, string $directory, bool $isPublic): string
    {
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

         $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME)).'-'.uniqid().'.'.pathinfo($originalFilename, PATHINFO_EXTENSION);
    #    $datfile_folder = $this->kernel->getProjectDir()."/public/uploads/"; //
    #    if (file_exists($datfile_folder.'/'.$directory.'/'.$newFilename)) {
    #     $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME)).'-'.uniqid().'.'.pathinfo($originalFilename, PATHINFO_EXTENSION);
    #    }

        $stream = fopen($file->getPathname(), 'r');

        $this->fileSystemFtp->writeStream(
            $directory.'/'.$newFilename,
            $stream,
            [
                'visibility' => $isPublic ? Visibility::PUBLIC : Visibility::PRIVATE,
            ]
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $newFilename;
    }
}

<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    public const PLANT_IMAGE = 'plants';
    public const PLANT_REFERENCE = 'plant_reference';
    public const EIGNER_LOGO = 'eigners';
    public const CSV = 'csv';

    private FilesystemInterface $filesystem;

    private RequestStackContext $requestStackContext;

    private LoggerInterface $logger;

    public function __construct(FilesystemInterface $filesystem, RequestStackContext $requestStackContext, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
    }

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

        $this->filesystem->write(
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

    public function uploadArticleReference(File $file): string
    {
        return $this->uploadFile($file, self::PLANT_REFERENCE, false);
    }

    public function getPublicPath(string $path): string
    {
        $fullPath = $this->publicAssetBaseUrl.'/'.$path;
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
     */
    public function readStream(string $path)
    {
        $resource = $this->filesystem->readStream($path);

        if ($resource === false) {
            throw new \Exception(sprintf('Error opening stream for "%s"', $path));
        }

        return $resource;
    }

    public function deleteFile(string $path)
    {
        $result = $this->filesystem->delete($path);

        if ($result === false) {
            throw new \Exception(sprintf('Error deleting "%s"', $path));
        }
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
        $result = $this->filesystem->writeStream(
            $directory.'/'.$newFilename,
            $stream,
            [
                'visibility' => $isPublic ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE,
            ]
        );

        if ($result === false) {
            throw new \Exception(sprintf('Could not write uploaded file "%s"', $newFilename));
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $newFilename;
    }
}

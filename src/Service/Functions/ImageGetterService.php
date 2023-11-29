<?php

namespace App\Service\Functions;

use App\Entity\Eigner;
use App\Helper\G4NTrait;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;

class ImageGetterService
{

    use G4NTrait;

    public function __construct(
        private Filesystem $fileSystemFtp,
        private Filesystem $filesystem,
    )
    {

    }

    /**
     * @throws FilesystemException
     * @throws \Exception
     */
    public function getOwnerLogo(Eigner $owner): bool|string
    {
        $logo = $return = null;
        if ($owner->getLogo() != '') {
            if ($this->fileSystemFtp->fileExists($owner->getLogo())) {
                $logo = $this->fileSystemFtp->read($owner->getLogo());
            }
        }

        if ($logo !== null) {
            $return = self::makeTempFiles([$logo], $this->filesystem)[0];
        }

        return $return;
    }
}
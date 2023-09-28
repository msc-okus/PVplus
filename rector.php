<?php

declare(strict_types=1);

use Rector\Symfony\Set\SymfonySetList;
use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonyLevelSetList;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->symfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml');

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_63,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);
    $rectorConfig->import(SymfonyLevelSetList::UP_TO_SYMFONY_63);
    $rectorConfig->import(SymfonySetList::SYMFONY_CODE_QUALITY);
    $rectorConfig->import(SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION);
    $rectorConfig->import(LevelSetList::UP_TO_PHP_81);
};

<?php

declare(strict_types=1);

use Rector\Symfony\Set\SymfonySetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $containerConfigurator): void {
    $containerConfigurator->symfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml');

    $containerConfigurator->import(\Rector\Doctrine\Set\DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES);
    $containerConfigurator->import(SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES);
    $containerConfigurator->import(\Rector\Symfony\Set\SensiolabsSetList::FRAMEWORK_EXTRA_61);
};

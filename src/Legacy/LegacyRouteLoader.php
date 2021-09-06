<?php

namespace App\Legacy;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class LegacyRouteLoader
 * @package App\Legacy
 * @deprecated
 */
class LegacyRouteLoader extends Loader
{
    private $webDir1 = __DIR__.'/../../pvp_v1/';


    public function load($resource, string $type = null)
    {
        $collection = new RouteCollection();
        $finder = new Finder();
        $finder->depth(0);
        $finder->files()->name('*.php');
        $finder->exclude([
            'incl', 'src', 'jsdb', 'module', 'lang', 'extern', 'cron'
        ]);

        /** @var SplFileInfo $legacyScriptFile */
        foreach ($finder->in($this->webDir1) as $legacyScriptFile) {
            // This assumes all legacy files use ".php" as extension
            $filename = basename($legacyScriptFile->getRelativePathname(), '.php');
            $routeName = sprintf('app.legacy.%s', str_replace('/', '__', $filename));
            $collection->add($routeName, new Route($legacyScriptFile->getRelativePathname(), [
                '_controller' => 'App\Controller\LegacyController::loadLegacyScript',
                'requestPath' => '/' . $legacyScriptFile->getRelativePathname(),
                'legacyScript' => $legacyScriptFile->getPathname(),
            ]));
        }
        return $collection;
    }

    public function supports($resource, string $type = null)
    {
        return 'extra' === $type;
    }
}

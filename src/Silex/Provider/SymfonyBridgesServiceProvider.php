<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Para información completa sobre los derechos de autor y licencia, por
 * favor, ve el archivo LICENSE que viene con este código fuente.
 */

namespace Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Symfony bridges Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SymfonyBridgesServiceProvider implements ServiceProviderInterface
{
    // BC: this class needs to be removed before 1.0
    public function __construct()
    {
        throw new \RuntimeException('You tried to create a SymfonyBridgesServiceProvider. However, it has been removed from Silex. Make sure that the Symfony bridge you want to use is autoloadable, and it will get loaded automatically. You should remove the creation of the SymfonyBridgesServiceProvider, as it is no longer needed.');
    }

    public function register(Application $app)
    {
    }

    public function boot(Application $app)
    {
    }
}

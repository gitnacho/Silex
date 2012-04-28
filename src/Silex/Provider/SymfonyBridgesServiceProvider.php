
.. code-block:: php

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
    public function register(Application $app)
    {
        $app['symfony_bridges'] = true;

        if (isset($app['symfony_bridges.class_path'])) {
            $app['autoloader']->registerNamespace('Symfony\\Bridge', $app['symfony_bridges.class_path']);
        }
    }
}

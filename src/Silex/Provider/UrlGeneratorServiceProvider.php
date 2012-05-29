
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

use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Symfony Routing component Provider for URL generation.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class UrlGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['url_generator'] = $app->share(function () use ($app) {
            $app->flush();

            return new UrlGenerator($app['routes'], $app['request_context']);
        });
    }

    public function boot(Application $app)
    {
    }
}

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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Monolog Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MonologServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if ($bridge = class_exists('Symfony\Bridge\Monolog\Logger')) {
            $app['logger'] = function () use ($app) {
                return $app['monolog'];
            };

            $app['monolog.handler.debug'] = function () use ($app) {
                return new DebugHandler($app['monolog.level']);
            };
        }

        $app['monolog.logger.class'] = $bridge ? 'Symfony\Bridge\Monolog\Logger' : 'Monolog\Logger';

        $app['monolog'] = $app->share(function ($app) {
            $log = new $app['monolog.logger.class']($app['monolog.name']);

            $log->pushHandler($app['monolog.handler']);

            if ($app['debug'] && isset($app['monolog.handler.debug'])) {
                $log->pushHandler($app['monolog.handler.debug']);
            }

            return $log;
        });

        $app['monolog.handler'] = function () use ($app) {
            return new StreamHandler($app['monolog.logfile'], $app['monolog.level']);
        };

        $app['monolog.level'] = function () {
            return Logger::DEBUG;
        };

        $app['monolog.name'] = 'myapp';
    }

    public function boot(Application $app)
    {
        // BC: to be removed before 1.0
        if (isset($app['monolog.class_path'])) {
            throw new \RuntimeException('You have provided the monolog.class_path parameter. Se ha eliminado de Silex el cargador automático. Se recomienda utilizar Composer para gestionar tus dependencias y manejar tu carga automática. If you are already using Composer, you can remove the parameter. Ve http://getcomposer.org para más información.');
        }

        $app->before(function (Request $request) use ($app) {
            $app['monolog']->addInfo('> '.$request->getMethod().' '.$request->getRequestUri());
        });

        $app->error(function (\Exception $e) use ($app) {
            $message = sprintf('%s: %s (uncaught exception) at %s line %s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                $app['monolog']->addError($message);
            } else {
                $app['monolog']->addCritical($message);
            }
        }, 255);

        $app->after(function (Request $request, Response $response) use ($app) {
            $app['monolog']->addInfo('< '.$response->getStatusCode());
        });
    }
}

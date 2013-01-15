<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Este archivo fuente está sujeto a la licencia MIT incluida con
 * este código fuente en el archivo LICENSE.
 */

namespace Silex\Tests;

use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Router test cases, using the ServiceControllerResolver
 */
class ServiceControllerResolverRouterTest extends RouterTest
{
    public function testServiceNameControllerSyntax()
    {
        $app = new Application();

        $app['service_name'] = function () {
            return new MyController;
        };

        $app->get('/bar', 'service_name:getBar');

        $this->checkRouteResponse($app, '/bar', 'bar');
    }

    protected function checkRouteResponse($app, $path, $expectedContent, $method = 'get', $message = null)
    {
        $app->register(new ServiceControllerServiceProvider());

        $request = Request::create($path, $method);
        $response = $app->handle($request);
        $this->assertEquals($expectedContent, $response->getContent(), $message);
    }
}

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

use Silex\ControllerResolver;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * ControllerResolver test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testGetArguments()
    {
        $app = new Application();
        $resolver = new ControllerResolver($app);

        $controller = function (Application $app) {};

        $args = $resolver->getArguments(Request::create('/'), $controller);
        $this->assertSame($app, $args[0]);
    }
}

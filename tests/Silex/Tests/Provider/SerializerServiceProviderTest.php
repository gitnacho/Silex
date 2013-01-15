<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Este archivo fuente está sujeto a la licencia MIT incluida con
 * este código fuente en el archivo LICENSE.
 */

namespace Silex\Tests\Provider;

use Silex\Application;
use Silex\Provider\SerializerServiceProvider;

/**
 * SerializerServiceProvider test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SerializerServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();

        $app->register(new SerializerServiceProvider);

        $this->assertInstanceOf("Symfony\Component\Serializer\Serializer", $app['serializer']);
        $this->assertTrue($app['serializer']->supportsEncoding('xml'));
        $this->assertTrue($app['serializer']->supportsEncoding('json'));
        $this->assertTrue($app['serializer']->supportsDecoding('xml'));
        $this->assertTrue($app['serializer']->supportsDecoding('json'));
    }
}

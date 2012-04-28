
.. code-block:: php

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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * JSON test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class JsonTest extends \PHPUnit_Framework_TestCase
{
    public function testJsonReturnsJsonResponse()
    {
        $app = new Application();

        $response = $app->json();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertSame('{}', $response->getContent());
    }

    public function testJsonUsesData()
    {
        $app = new Application();

        $response = $app->json(array('foo' => 'bar'));
        $this->assertSame('{"foo":"bar"}', $response->getContent());
    }

    public function testJsonUsesStatus()
    {
        $app = new Application();

        $response = $app->json(array(), 202);
        $this->assertSame(202, $response->getStatusCode());
    }

    public function testJsonUsesHeaders()
    {
        $app = new Application();

        $response = $app->json(array(), 200, array('ETag' => 'foo'));
        $this->assertSame('foo', $response->headers->get('ETag'));
    }
}

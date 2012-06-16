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
 * Stream test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class StreamTest extends \PHPUnit_Framework_TestCase
{
    public function testStreamReturnsStreamingResponse()
    {
        $app = new Application();

        $response = $app->stream();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $response);
        $this->assertSame(false, $response->getContent());
    }

    public function testStreamActuallyStreams()
    {
        $i = 0;

        $stream = function () use (&$i) {
            $i++;
        };

        $app = new Application();
        $response = $app->stream($stream);

        $this->assertEquals(0, $i);

        $request = Request::create('/stream');
        $response->prepare($request);
        $response->sendContent();

        $this->assertEquals(1, $i);
    }
}

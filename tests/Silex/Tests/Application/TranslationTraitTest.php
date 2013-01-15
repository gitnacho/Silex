<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Este archivo fuente está sujeto a la licencia MIT incluida con
 * este código fuente en el archivo LICENSE.
 */

namespace Silex\Tests\Application;

use Silex\Application;
use Silex\Provider\TranslationServiceProvider;

/**
 * TranslationTrait test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TranslationTraitTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (version_compare(phpversion(), '5.4.0', '<')) {
            $this->markTestSkipped('PHP 5.4 is required for this test');
        }

        if (!is_dir(__DIR__.'/../../../../vendor/symfony/translation')) {
            $this->markTestSkipped('Translation dependency was not installed.');
        }
    }

    public function testTrans()
    {
        $app = $this->createApplication();
        $app['translator'] = $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')->disableOriginalConstructor()->getMock();
        $translator->expects($this->once())->method('trans');
        $app->trans('foo');
    }

    public function testTransChoice()
    {
        $app = $this->createApplication();
        $app['translator'] = $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')->disableOriginalConstructor()->getMock();
        $translator->expects($this->once())->method('transChoice');
        $app->transChoice('foo', 2);
    }

    public function createApplication()
    {
        $app = new TranslationApplication();
        $app->register(new TranslationServiceProvider());

        return $app;
    }
}

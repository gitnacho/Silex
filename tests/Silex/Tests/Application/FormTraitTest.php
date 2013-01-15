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
use Silex\Provider\FormServiceProvider;

/**
 * FormTrait test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FormTraitTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (version_compare(phpversion(), '5.4.0', '<')) {
            $this->markTestSkipped('PHP 5.4 is required for this test');
        }

        if (!is_dir(__DIR__.'/../../../../vendor/symfony/form')) {
            $this->markTestSkipped('Form dependency was not installed.');
        }
    }

    public function testForm()
    {
        $this->assertInstanceOf('Symfony\Component\Form\FormBuilder', $this->createApplication()->form());
    }

    public function createApplication()
    {
        $app = new FormApplication();
        $app->register(new FormServiceProvider());

        return $app;
    }
}

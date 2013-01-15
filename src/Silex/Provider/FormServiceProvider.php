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
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension as FormValidatorExtension;
use Symfony\Component\Form\Forms;

/**
 * Symfony Form component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FormServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!class_exists('Locale') && !class_exists('Symfony\Component\Locale\Stub\StubLocale')) {
            throw new \RuntimeException('You must either install the PHP intl extension or the Symfony Locale Component to use the Form extension.');
        }

        if (!class_exists('Locale')) {
            $r = new \ReflectionClass('Symfony\Component\Locale\Stub\StubLocale');
            $path = dirname(dirname($r->getFilename())).'/Resources/stubs';

            require_once $path.'/functions.php';
            require_once $path.'/Collator.php';
            require_once $path.'/IntlDateFormatter.php';
            require_once $path.'/Locale.php';
            require_once $path.'/NumberFormatter.php';
        }

        $app['form.secret'] = md5(__DIR__);

        $app['form.extensions'] = $app->share(function ($app) {
            $extensions = array(
                new CsrfExtension($app['form.csrf_provider']),
                new HttpFoundationExtension(),
            );

            if (isset($app['validator'])) {
                $extensions[] = new FormValidatorExtension($app['validator']);

                if (isset($app['translator'])) {
                    $r = new \ReflectionClass('Symfony\Component\Form\Form');
                    $app['translator']->addResource('xliff', dirname($r->getFilename()).'/Resources/translations/validators.'.$app['locale'].'.xlf', $app['locale'], 'validators');
                }
            }

            return $extensions;
        });

        $app['form.factory'] = $app->share(function ($app) {
            return Forms::createFormFactoryBuilder()
                ->addExtensions($app['form.extensions'])
                ->getFormFactory()
            ;
        });

        $app['form.csrf_provider'] = $app->share(function ($app) {
            if (isset($app['session'])) {
                return new SessionCsrfProvider($app['session'], $app['form.secret']);
            }

            return new DefaultCsrfProvider($app['form.secret']);
        });
    }

    public function boot(Application $app)
    {
        // BC: to be removed before 1.0
        if (isset($app['form.class_path'])) {
            throw new \RuntimeException('You have provided the form.class_path parameter. Se ha eliminado de Silex el cargador automático. Se recomienda utilizar Composer para gestionar tus dependencias y manejar tu carga automática. If you are already using Composer, you can remove the parameter. Ve http://getcomposer.org para más información.');
        }
    }
}

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

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;

/**
 * Symfony Translation component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['translator'] = $app->share(function ($app) {
            $translator = new Translator($app['locale'], $app['translator.message_selector']);

            $translator->setFallbackLocale($app['locale_fallback']);

            $translator->addLoader('array', new ArrayLoader());
            $translator->addLoader('xliff', new XliffFileLoader());

            foreach ($app['translator.domains'] as $domain => $data) {
                foreach ($data as $locale => $messages) {
                    $translator->addResource('array', $messages, $locale, $domain);
                }
            }

            return $translator;
        });

        $app['translator.message_selector'] = $app->share(function () {
            return new MessageSelector();
        });

        $app['translator.domains'] = array();

        $app['locale_fallback'] = 'en';
    }

    public function boot(Application $app)
    {
        // BC: to be removed before 1.0
        if (isset($app['translation.class_path'])) {
            throw new \RuntimeException('You have provided the translation.class_path parameter. Se ha eliminado de Silex el cargador automático. Se recomienda utilizar Composer para gestionar tus dependencias y manejar tu carga automática. If you are already using Composer, you can remove the parameter. Ve http://getcomposer.org para más información.');
        }
    }
}

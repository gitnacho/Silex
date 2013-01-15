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

use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\ConstraintValidatorFactory;

/**
 * Symfony Validator component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ValidatorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['validator'] = $app->share(function ($app) {
            if (isset($app['translator'])) {
                $r = new \ReflectionClass('Symfony\Component\Validator\Validator');
                $app['translator']->addResource('xliff', dirname($r->getFilename()).'/Resources/translations/validators.'.$app['locale'].'.xlf', $app['locale'], 'validators');
            }

            return new Validator(
                $app['validator.mapping.class_metadata_factory'],
                $app['validator.validator_factory']
            );
        });

        $app['validator.mapping.class_metadata_factory'] = $app->share(function ($app) {
            return new ClassMetadataFactory(new StaticMethodLoader());
        });

        $app['validator.validator_factory'] = $app->share(function () {
            return new ConstraintValidatorFactory();
        });
    }

    public function boot(Application $app)
    {
        // BC: to be removed before 1.0
        if (isset($app['validator.class_path'])) {
            throw new \RuntimeException('You have provided the validator.class_path parameter. Se ha eliminado de Silex el cargador automático. Se recomienda utilizar Composer para gestionar tus dependencias y manejar tu carga automática. If you are already using Composer, you can remove the parameter. Ve http://getcomposer.org para más información.');
        }
    }
}

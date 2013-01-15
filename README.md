Silex, una sencilla plataforma web
==================================

[![Build Status](https://secure.travis-ci.org/fabpot/Silex.png?branch=master)](http://travis-ci.org/fabpot/Silex)

Silex es una microplataforma web para desarrollar sencillos sitios web basados en componentes de [Symfony2][1]:


```php
<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app->get('/hello/{name}', function ($name) use ($app) {
  return 'Hello '.$app->escape($name);
});

$app->run();
```

Silex trabaja con PHP 5.3.3 o más reciente.

## Instalando

La forma recomendada de instalar Silex es [a través de Composer](http://getcomposer.org). Sólo crea un archivo `composer.json` y ejecuta la orden `php composer.phar install` para instalarlo:

    {
        "require": {
            "silex/silex": "1.0.*@dev"
        }
    }

Alternativamente, puedes descargar el archivo [`silex.zip`][2] y descomprimirlo.

## Más Información

Lee la [documentación][3] para más información.

## Pruebas

Para ejecutar la batería de pruebas necesitas
[composer](http://getcomposer.org) y
[PHPUnit](https://github.com/sebastianbergmann/phpunit).

    $ php composer.phar install --dev
    $ phpunit

## Comunidad

Échale un vistazo a #silex-php en irc.freenode.net.

## Licencia

Silex se libera bajo la licencia MIT.

[1]: http://symfony.com
[2]: http://silex.sensiolabs.org/download
[3]: http://gitnacho.github.com/Silex

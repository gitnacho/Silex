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

Instalar Silex es tan fácil como lo puedas obtener. Descarga el archivo [silex.zip][2], ¡descomprímelo y listo!

## Más Información

Lee la [documentación][3] para más información.

## Pruebas

Para ejecutar el banco de pruebas necesitas
[composer](http://getcomposer.org) y
[PHPUnit](https://github.com/sebastianbergmann/phpunit).

    $ php composer.phar install --dev
    $ phpunit

## Licencia

Silex se libera bajo la licencia MIT.

[1]: http://symfony.com
[2]: http://silex.sensiolabs.org/download
[3]: http://gitnacho.github.com/Silex

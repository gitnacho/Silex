Introducción
============

*Silex* es una microplataforma *PHP* para *PHP* 5.3. Está construida sobre los hombros de *Symfony2* y *Pimple* además de inspirada en ``Sinatra``.

Una microplataforma proporciona la base para construir aplicaciones simples de un solo archivo. *Silex* pretende ser:

* *Concisa*: Silex expone una intuitiva y concisa API que es divertido utilizar.

* *Extensible*: *Silex* cuenta con un sistema de extensión en torno al microcontenedor de servicios ``Pimple`` que lo hace aún más fácil de encajar con bibliotecas de terceros.

* *Comprobable*: *Silex* utiliza el ``HttpKernel`` de *Symfony2* el cual abstrae las peticiones y respuestas. Esto hace que sea muy fácil probar las aplicaciones y la propia plataforma. También respeta la especificación *HTTP* y alienta su uso adecuado.

En pocas palabras, tú defines controladores y asignas rutas, en un solo paso.

**¡Comencemos!**::

    // web/index.php

    require_once __DIR__.'/../vendor/autoload.php';

    $app = new Silex\Application();

    $app->get('/hello/{name}', function ($name) use ($app) {
        return 'Hello '.$app->escape($name);
    });

    $app->run();

Todo lo que necesitas para acceder a la plataforma es incluir el cargador automático.

A continuación defines una ruta a ``/hello/{name}`` que corresponde a las peticiones ``GET``.
Cuando la ruta coincide, se ejecuta la función y el valor de retorno se devuelve al cliente.

Por último, se ejecuta la aplicación. Visita ``/hello/world`` para ver el resultado. ¡Así de fácil!

Instalar Silex es tan fácil como lo puedas obtener. `Descarga`_ el archivo, descomprímelo, ¡y listo!

.. _`Descarga`: http://silex.sensiolabs.org/download

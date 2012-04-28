``UrlGeneratorServiceProvider``
===============================

El ``UrlGeneratorServiceProvider`` ofrece un servicio para generar *URL* para las rutas con nombre.

Parámetros
----------

Ninguno.

Servicios
---------

* **url_generator**: Una instancia del `UrlGenerator <http://api.symfony.com/master/Symfony/Component/Routing/Generator/UrlGenerator.html>`_, usando la `RouteCollection <http://api.symfony.com/master/Symfony/Component/Routing/RouteCollection.html>`_ proporcionada a través del servicio ``routes``.
  Tiene un método ``generate``, el cual toma el nombre de la ruta como argumento, seguido por un arreglo de parámetros de la ruta.

Registrando
-----------

::

    $app->register(new Silex\Provider\UrlGeneratorServiceProvider());

Uso
---

El proveedor ``UrlGenerator`` ofrece un servicio ``url_generator``::

    $app->get('/', function () {
        return 'welcome to the homepage';
    })
    ->bind('homepage');

    $app->get('/hello/{name}', function ($name) {
        return "Hello $name!";
    })
    ->bind('hello');

    $app->get('/navigation', function () use ($app) {
        return '<a href="'.$app['url_generator']->generate('homepage').'">Home</a>'.
               ' | '.
               '<a href="'.$app['url_generator']->generate('hello', array('name' => 'Igor')).'">Hello Igor</a>';
    });

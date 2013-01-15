Modularidad
===========

Cuando tu aplicación comienza a definir muchos controladores, es posible que desees agruparlos lógicamente::

    // define controladores para un blog
    $blog = $app['controllers_factory'];
    $blog->get('/', function () {
        return 'Blog home page';
    });
    // ...

    // define controladores para un foro
    $forum = $app['controllers_factory'];
    $forum->get('/', function () {
        return 'Forum home page';
    });

    // define controladores 'globales'
    $app->get('/', function () {
        return 'Main home page';
    });

    $app->mount('/blog', $blog);
    $app->mount('/forum', $forum);

.. note::

    ``$app['controllers_factory']`` es una fábrica que --- cuando se utiliza--- devuelve una nueva instancia de ``ControllerCollection``.

``mount()`` prefija todas las rutas con la cadena dada y las integra en la aplicación principal. Por lo tanto, ``/`` se asignará a la página principal, ``/blog/`` a la página principal del blog, y ``/forum/`` a la página principal del foro.

.. caution::

    Cuando montes una colección de rutas bajo ``/blog``, no es posible definir una ruta para la dirección *URL* ``/blog``. La *URL* más corta posible es ``/blog/``.

.. note::

    Cuando invocas a ``get()``, ``match()``, o cualquier otro método *HTTP* de la aplicación, de hecho, los estás llamando en una instancia predeterminada de ``ControllerCollection`` (almacenado en ``$app['controllers']``).

Otra ventaja es la capacidad para aplicar muy fácilmente la configuración a un conjunto de controladores. Basándonos en el ejemplo de la sección de la lógica intermedia, aquí tienes cómo protegeríamos todos los controladores para la colección ``backend``::

    $backend = $app['controllers_factory'];

    // garantiza que todos los controladores requieren usuarios que
    // hayan iniciado sesión
    $backend->before($mustBeLogged);

.. tip::

    Para mejorar la legibilidad, puedes dividir cada colección de controladores en un archivo independiente::

        // blog.php
        $blog = $app['controllers_factory'];
        $blog->get('/', function () { return 'Blog home page'; });

        return $blog;

        // app.php
        $app->mount('/blog', include 'blog.php');

    En lugar de necesitar un archivo, también puedes crear un :ref:`Proveedor de controladores <proveedor-de-controladores>`.

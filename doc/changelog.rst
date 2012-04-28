Registro de cambios
===================

Este registro de cambios refiere todas las incompatibilidades con versiones anteriores conforme se presentaron:

* **2012-03-20**: Añadido el ayudante ``json``::

        $data = array('some' => 'data');
        $response = $app->json($data);

* **2012-03-11**: Añadido soporte lógico intermedio de ruta

* **2012-03-02**: Ahora utiliza **Composer** para gestionar dependencias

* **2012-02-27**: Manipulación de sesión actualizada a *Symfony 2.1*.

* **2012-01-02**: Introdujo el apoyo para respuestas que transmiten secuencias.

* **2011-09-22**: ``ExtensionInterface`` se le cambió el nombre a ``ServiceProviderInterface``. Todas las extensiones integradas se han renombrado consecuentemente (por ejemplo, ``Silex\Extension\TwigExtension`` ha cambiado el nombre a ``Silex\Provider\TwigServiceProvider``)

* **2011-09-22**: La forma de trabajar de las aplicaciones reutilizables ha cambiado. El método ``mount()`` ahora toma una instancia de ``ControllerCollection`` en lugar de una ``Application``.

    Antes::

        $app = new Application();
        $app->get('/bar', function() { return 'foo'; });

        return $app;

    Después::

        $app = new ControllerCollection();
        $app->get('/bar', function() { return 'foo'; });

        return $app;

* **2011-08-08**: La configuración del método controlador ahora se hace en el propio ``Controlador``

    Antes::

        $app->match('/', function () { echo 'foo'; }, 'GET|POST');

    Después::

        $app->match('/', function () { echo 'foo'; })->method('GET|POST');

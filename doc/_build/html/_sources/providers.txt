Proveedores
===========

Los proveedores permiten al desarrollador reutilizar partes de una aplicación en otra. *Silex* ofrece dos tipos de proveedores definidos por dos interfaces:
``ServiceProviderInterface`` para los servicios y ``ControllerProviderInterface`` para los controladores.

Proveedores de servicios
------------------------

Cargando proveedores
~~~~~~~~~~~~~~~~~~~~

Con el fin de cargar y usar un proveedor de servicios, lo debes registrar en la aplicación::

    $app = new Silex\Application();

    $app->register(new Acme\DatabaseServiceProvider());

También puedes proporcionar algunos parámetros como segundo argumento. Estos se deben establecer **antes** de registrar al proveedor::

    $app->register(new Acme\DatabaseServiceProvider(), array(
        'database.dsn'      => 'mysql:host=localhost;dbname=myapp',
        'database.user'     => 'root',
        'database.password' => 'secret_root_password',
    ));

Convenciones
~~~~~~~~~~~~

Necesitas tener cuidado con el orden en que haces ciertas cosas cuando interactúas con proveedores. Sólo sigue estas reglas:

* La redefinición de servicios existentes debe ocurrir **después** de haber registrado el proveedor.

  *Razón: Si los servicios ya existen, el proveedor los sobrescribirá.*

* Puedes configurar los parámetros en cualquier momento antes de acceder al servicio.

Asegúrate de que te adhieres a este comportamiento al crear tus propios proveedores.

Proveedores integrados
~~~~~~~~~~~~~~~~~~~~~~

Hay algunos proveedores que obtienes fuera de la caja. Todos ellos están dentro del espacio de nombres ``Silex\Provider``.

* :doc:`DoctrineServiceProvider <providers/doctrine>`
* :doc:`MonologServiceProvider <providers/monolog>`
* :doc:`SessionServiceProvider <providers/session>`
* :doc:`SwiftmailerServiceProvider <providers/swiftmailer>`
* :doc:`TwigServiceProvider <providers/twig>`
* :doc:`TranslationServiceProvider <providers/translation>`
* :doc:`UrlGeneratorServiceProvider <providers/url_generator>`
* :doc:`ValidatorServiceProvider <providers/validator>`
* :doc:`HttpCacheServiceProvider <providers/http_cache>`
* :doc:`FormServiceProvider <providers/form>`

Proveedores de terceros
~~~~~~~~~~~~~~~~~~~~~~~

Algunos proveedores de servicios son desarrollados por la comunidad. Esos proveedores están listados en el `wiki del repositorio de Silex <https://github.com/fabpot/Silex/wiki/Third-Party-ServiceProviders>`_.

Te animamos a compartir los tuyos.

Creando un proveedor
~~~~~~~~~~~~~~~~~~~~

Los proveedores deben implementar el ``Silex\ServiceProviderInterface``::

    interface ServiceProviderInterface
    {
        function register(Application $app);

        function boot(Application $app);
    }

Esto es muy sencillo, basta con crear una nueva clase que implemente los dos métodos. En el método ``register()`` tienes que definir los servicios de la aplicación que pueden usar otros servicios y parámetros. En el método ``boot()`` puedes configurar la aplicación, justo antes de manipular una petición.

He aquí un ejemplo de tal proveedor::

    namespace Acme;

    use Silex\Application;
    use Silex\ServiceProviderInterface;

    class HelloServiceProvider implements ServiceProviderInterface
    {
        public function register(Application $app)
        {
            $app['hello'] = $app->protect(function ($name) use ($app) {
                $default = $app['hello.default_name'] ? $app['hello.default_name'] : '';
                $name = $name ?: $default;

                return 'Hello '.$app->escape($name);
            });
        }

        public function boot(Application $app)
        {
        }
    }

Esta clase proporciona un servicio, ``hello``, el cual es un cierre protegido. Este toma un argumento nombre y devolverá ``hello.default_name`` si no se da un nombre. Si además falta el predeterminado, utilizará una cadena vacía.

Ahora puedes utilizar este proveedor de la siguiente manera:

.. code-block:: php

    $app = new Silex\Application();

    $app->register(new Acme\HelloServiceProvider(), array(
        'hello.default_name' => 'Igor',
    ));

    $app->get('/hello', function () use ($app) {
        $name = $app['request']->get('name');

        return $app['hello']($name);
    });

En este ejemplo estamos obteniendo el parámetro ``name`` desde la cadena de consulta, por lo que la ruta de la petición tendría que ser ``/hello?name=Fabien``.

Proveedores de controladores
----------------------------

Cargando proveedores
~~~~~~~~~~~~~~~~~~~~

Con el fin de cargar y usar un controlador del proveedor, debes "montar" tus controladores en una ruta::

    $app = new Silex\Application();

    $app->mount('/blog', new Acme\BlogControllerProvider());

Todos los controladores definidos por el proveedor ahora estarán disponibles bajo la ruta ``/blog``.

Creando un proveedor
~~~~~~~~~~~~~~~~~~~~

Los proveedores deben implementar la ``Silex\ControllerProviderInterface``::

    interface ControllerProviderInterface
    {
        function connect(Application $app);
    }

He aquí un ejemplo de tal proveedor::

    namespace Acme;

    use Silex\Application;
    use Silex\ControllerProviderInterface;
    use Silex\ControllerCollection;

    class HelloControllerProvider implements ControllerProviderInterface
    {
        public function connect(Application $app)
        {
            $controllers = new ControllerCollection();

            $controllers->get('/', function (Application $app) {
                return $app->redirect('/hello');
            });

            return $controllers;
        }
    }

El método ``connect`` debe regresar una instancia de ``ControllerCollection``.
``ControllerCollection`` es la clase donde todos los métodos controladores relacionados están definidos (como ``get``, ``post``, ``match``, ...).

.. tip::

    La clase ``Application`` de hecho actúa en un delegado para estos métodos.

Ahora puedes utilizar este proveedor de la siguiente manera:

.. code-block:: php

    $app = new Silex\Application();

    $app->mount('/blog', new Acme\HelloControllerProvider());

En este ejemplo, la ruta ``/blog/`` ahora hace referencia al controlador definido en el proveedor.

.. tip::

    También puedes definir un proveedor que implemente ambos, el servicio y la interfaz del proveedor del controlador y envasar en la misma clase los servicios necesarios para hacer que tu controlador funcione.

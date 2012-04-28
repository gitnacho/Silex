Servicios
=========

*Silex* no es sólo una microplataforma. También es un microcontenedor de servicios. Esto lo consigue extendiendo a `Pimple <http://pimple-project.org>`_ que ofrece el bondadoso servicio en sólo 44 *NCLOC*.

Inyección de dependencias
-------------------------

.. note::

    Puedes omitir esto si ya sabes lo que es la inyección de dependencias.

La inyección de dependencias es un patrón de diseño dónde pasas las dependencias a servicios en lugar de crearlas desde dentro del servicio o depender de variables globales. Esto generalmente lleva a un código disociado, reutilizable, flexible y fácil de probar.

He aquí un ejemplo de una clase que toma un objeto ``Usuario`` y lo guarda como un archivo en formato *JSON*::

    class JsonUserPersister
    {
        private $basePath;

        public function __construct($basePath)
        {
            $this->basePath = $basePath;
        }

        public function persist(User $user)
        {
            $data = $user->getAttributes();
            $json = json_encode($data);
            $filename = $this->basePath.'/'.$user->id.'.json';
            file_put_contents($filename, $json, LOCK_EX);
        }
    }

En este sencillo ejemplo la dependencia es la propiedad ``basePath``.
Esta se pasa al constructor. Esto significa que puedes crear varias instancias independientes con diferentes rutas base. Por supuesto, las dependencias no tienen que ser simples cadenas de texto. Muy a menudo estas, de hecho, se encuentran en otros servicios.

Contenedor
~~~~~~~~~~

Un ``IDC`` o contenedor de servicio es responsable de crear y almacenar servicios. Este puede crear recurrentemente dependencias de los servicios solicitados e inyectarlos. Lo hace de manera diferida, lo cual significa que un servicio sólo se crea cuando realmente se necesita.

La mayoría de los contenedores son muy complejos y se configuran a través de archivos *XML* o *YAML*.

*Pimple* es diferente

*Pimple*
--------

*Pimple*, probablemente, es el más simple contenedor de servicios que hay. Usa exhaustivamente los cierres que implementan la interfaz ``ArrayAccess``.

Vamos a empezar por crear una nueva instancia de *Pimple* -- y puesto que ``Silex\application`` extiende a *Pimple* todo esto se aplica a *Silex* también:

.. code-block:: php

    $container = new Pimple();

o:

.. code-block:: php

    $app = new Silex\Application();

Parámetros
~~~~~~~~~~

Puedes establecer los parámetros (los cuales suelen ser cadenas) estableciendo una clave en el arreglo del contenedor::

    $app['algún_parámetro'] = 'valor';

La clave del arreglo puede ser cualquier cosa, por convención se utilizan puntos para denominar los espacios de nombres::

    $app['activo.anfitrión'] = 'http://cdn.misitio.com/';

Es posible leer valores de parámetros con la misma sintaxis:

.. code-block:: php

    echo $app['algún_parámetro'];

Definiendo servicios
~~~~~~~~~~~~~~~~~~~~


La definición de servicios no es diferente de la definición de parámetros.
Sólo tienes que establecer una clave en el arreglo del contenedor a un cierre.
Sin embargo, cuando recuperes el servicio, se ejecuta el cierre.
Esto permite la creación diferida de servicios::

    $app['some_service'] = function () {
        return new Service();
    };

Y para recuperar el servicio, utiliza::

    $service = $app['some_service'];

Cada vez que llames a  ``$app['some_service']``, se crea una nueva instancia del servicio.

Servicios compartidos
~~~~~~~~~~~~~~~~~~~~~

Posiblemente desees utilizar la misma instancia de un servicio a través de todo el código. A fin de que puedas hacer *compartido* un servicio::

    $app['some_service'] = $app->share(function () {
        return new Service();
    });

Esto creará el servicio en la primera invocación, y luego devolverá la instancia existente en cualquier acceso posterior.

Accediendo al contenedor desde un cierre
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

En muchos casos, desearás acceder al contenedor de servicios dentro de un cierre en la definición del servicio. Por ejemplo, al recuperar servicios de los que depende el servicio actual.

Debido a esto, el contenedor se pasa al cierre como argumento::

    $app['some_service'] = function ($app) {
        return new Service($app['some_other_service'], $app['some_service.config']);
    };

Aquí puedes ver un ejemplo de Inyección de dependencias.
``some_service`` depende de ``some_other_service`` y toma ``some_service.config`` como opciones de configuración.
La dependencia sólo se crea cuando accedes a ``some_service``, y es posible reemplazar cualquiera de las dependencias simplemente remplazando esas definiciones.

.. note::

    Esto también trabaja para los servicios compartidos.

.. _cierres-protegidos:

Cierres protegidos
~~~~~~~~~~~~~~~~~~

Debido a que el contenedor ve los cierres cómo fábricas de servicios, siempre se deben ejecutar cuando los leas.

En algunos casos, sin embargo, deseas almacenar un cierre como un parámetro, de modo que lo puedas recuperar y ejecutar tú mismo -- con tus propios argumentos.

Esta es la razón por la cual *Pimple* te permite proteger tus cierres de ser ejecutados, usando el método ``protect``::

    $app['closure_parameter'] = $app->protect(function ($a, $b) {
        return $a + $b;
    });

    // no debe ejecutar el cierre
    $add = $app['closure_parameter'];

    // llamándolo ahora
    echo $add(2, 3);

Ten en cuenta que los cierres protegidos no tienen acceso al contenedor.

Servicios básicos
-----------------

*Silex* define una serie de servicios que puedes utilizar o reemplazar. Es probable que no quieras meterte con la mayoría de ellos.

* **request**: Contiene el objeto ``Petición`` actual, el cual es una instancia de `Request <http://api.symfony.com/master/Symfony/Component/HttpFoundation/Request.html>`_.
  ¡Este proporciona acceso a los parámetros ``GET``, ``POST`` y mucho más!

  Ejemplo de uso::

    $id = $app['request']->get('id');

  Este sólo está disponible cuando se está sirviendo la petición, sólo puedes acceder a él desde dentro de un controlador, antes del filtro, después del filtro o al manejar algún error.

* **autoloader**: Este servicio te proporciona un `UniversalClassLoader <http://api.symfony.com/master/Symfony/Component/ClassLoader/UniversalClassLoader.html>`_ que ya está registrado. Puedes registrar prefijos y espacios de nombres en él.

  Ejemplo de uso, autocargando clases *Twig*::

    $app['autoloader']->registerPrefix('Twig_', $app['twig.class_path']);

  Para más información, consulta la documentación del `autocargador de Symfony2 <http://gitnacho.github.com/symfony-docs-es/components/class_loader.html>`_.

* **routes**: El `RouteCollection <http://api.symfony.com/master/Symfony/Component/Routing/RouteCollection.html>`_ utilizado internamente. Puedes agregar, modificar y leer rutas.

* **controllers**: La ``Silex\ControllerCollection`` utilizada internamente. Consulta el capítulo :doc:`internals` para más información.

* **dispatcher**: El `EventDispatcher <http://api.symfony.com/master/Symfony/Component/EventDispatcher/EventDispatcher.html>`_ utilizado internamente. Es el núcleo del sistema *Symfony2* y se utiliza un poco en *Silex*.

* **resolver**: El `ControllerResolver <http://api.symfony.com/master/Symfony/Component/HttpKernel/Controller/ControllerResolver.html>`_ utilizado internamente. Se encarga de ejecutar el controlador con los argumentos adecuados.

* **kernel**: El `HttpKernel <http://api.symfony.com/master/Symfony/Component/HttpKernel/HttpKernel.html>`_ utilizado internamente. El ``HttpKernel`` es el corazón de *Symfony2*, este toma una Petición como entrada y devuelve una Respuesta como salida.

* **request_context**: El contexto de la petición es una representación simplificada de la petición que utilizan el ``Router`` y el ``UrlGenerator``.

* **exception_handler**: El controlador de excepciones es el controlador predeterminado que se utiliza cuando no registras uno a través del método ``error()`` o si el controlador no devuelve una Respuesta. Lo puedes desactivar con ``unset($app['exception_handler'])``.

.. note::

    Todos estos servicios básicos de *Silex* son compartidos.

Parámetros básicos
------------------

* **request.http_port** (opcional): Te permite redefinir el puerto predeterminado para direcciones que no sean *HTTPS*. Si la petición actual es *HTTP*, siempre utiliza el puerto actual.

  El predeterminado es 80.

  Este parámetro lo puede utilizar el ``UrlGeneratorProvider``.

* **request.https_port** (opcional): Te permite redefinir el puerto predeterminado para direcciones que no sean *HTTPS*. Si la petición actual es *HTTPS*, siempre usará el puerto actual.

  Predeterminado a 443.

  Este parámetro lo puede utilizar el ``UrlGeneratorProvider``.

* **request.default_locale** (opcional): La región usada por omisión.

  Predeterminada a ``en``.

* **debug** (opcional): Indica si o no se ejecuta la aplicación en modo de depuración.

  El valor predeterminado es ``false``.

* **charset** (opcional): El juego de caracteres a usar para las Respuestas.

  Por omisión es UTF-8.

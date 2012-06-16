Usándola
========

Este capítulo describe cómo utilizar *Silex*.

Instalando
----------

Si quieres empezar rápidamente, `descarga`_ *Silex* como un archivo y descomprímelo, deberías tener la siguiente estructura de directorios:

.. code-block:: text

    ├── composer.json
    ├── composer.lock
    ├── vendor
    │   └── ...
    └── web
        └── index.php

Si quieres más flexibilidad, en su lugar usa ``Composer``. Crea un archivo :file:`composer.json`:

.. code-block:: javascript

    {
        "require": {
            "silex/silex": "1.0.*"
        }
    }

Y corre ``Composer`` para instalar *Silex* y todas sus dependencias:

.. code-block:: bash

    $ curl -s http://getcomposer.org/installer | php
    $ composer.phar install

Actualizando
------------

Actualizar *Silex* a la versión más reciente es tan fácil como ejecutar la orden ``update``::

    $ composer.phar update

Arranque
--------

Para arrancar *Silex*, todo lo que necesitas hacer es requerir el archivo :file:`vendor/autoload.php` y crear una instancia de ``Silex\Application``. Después de definir tu controlador, llama al método ``run`` en tu aplicación::

    // web/index.php

    require_once __DIR__.'/../vendor/autoload.php';

    $app = new Silex\Application();

    // definiciones

    $app->run();

Entonces, tienes que configurar tu servidor *web* (ve los consejos para *Apache* e *IIS* más adelante).

.. tip::

    Cuando desarrollas un sitio *web*, posiblemente quieras activar el modo de depuración para que se te facilite la corrección de errores::

        $app['debug'] = true;

.. tip::

    Si tu aplicación se encuentra detrás de un delegado inverso y deseas que *Silex* compruebe las cabeceras ``X-Forwarded-For*``, tendrás que ejecutar tu aplicación de esta manera::

        use Symfony\Component\HttpFoundation\Request;

        Request::trustProxyData();
        $app->run();

Enrutado
--------

En *Silex* defines simultáneamente una ruta y el controlador que se invocará cuando dicha ruta concuerde

Un patrón de ruta se compone de:

* *Pattern*: El patrón de ruta define una ruta que apunta a un recurso. El patrón puede incluir partes variables y tú podrás establecer los requisitos con expresiones regulares.

* *Method*: Uno de los siguientes métodos *HTTP*: ``GET``, ``POST``, ``PUT`` o ``DELETE``. Este describe la interacción con el recurso. Normalmente sólo se utilizan ``GET`` y ``POST``, pero, también es posible utilizar los otros.

El controlador se define usando un cierre de esta manera:

.. code-block:: php

    function () {
        // hace algo
    }

Los cierres son funciones anónimas que pueden importar el estado desde fuera de su definición. Esto es diferente de las variables globales, porque el estado exterior no tiene que ser global. Por ejemplo, podrías definir un cierre en una función e importar variables locales desde esa función.

.. note::

    Los cierres que no importan el ámbito se conocen como lambdas. Debido a que en *PHP* todas las funciones anónimas son instancias de la clase ``Closure``, no vamos a hacer una distinción aquí.

El valor de retorno del cierre se convierte en el contenido de la página.

También existe una forma alterna para definir controladores que utilizan un método de clase.
La sintaxis para esto es **NombreClase**::**nombreMétodo**. También son posibles los métodos estáticos.

Ejemplo de ruta ``GET``
~~~~~~~~~~~~~~~~~~~~~~~

He aquí un ejemplo de una definición de ruta ``GET``::

    $blogPosts = array(
        1 => array(
            'date'      => '2011-03-29',
            'author'    => 'igorw',
            'title'     => 'Using Silex',
            'body'      => '...',
        ),
    );

    $app->get('/blog', function () use ($blogPosts) {
        $output = '';
        foreach ($blogPosts as $post) {
            $output .= $post['title'];
            $output .= '<br />';
        }

        return $output;
    });

Al visitar ``/blog`` devolverá una lista con los títulos de los artículos en el ``blog``. La declaración ``use`` significa algo diferente en este contexto. Esta instruye al cierre para que importe la variable ``$blogPosts`` desde el ámbito externo. Esto te permite utilizarla dentro del cierre.

Enrutado dinámico
~~~~~~~~~~~~~~~~~

Ahora, puedes crear otro controlador para ver artículos individuales del ``blog``::

    $app->get('/blog/show/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
        if (!isset($blogPosts[$id])) {
            $app->abort(404, "Post $id does not exist.");
        }

        $post = $blogPosts[$id];

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });

Esta definición de ruta tiene una parte variable ``{id}`` que se pasa al cierre.

Cuando el artículo no existe, usamos ``abort()`` para detener la petición inicial. En realidad, se produce una excepción, la cual veremos cómo manejar más adelante.

Ejemplo de ruta ``POST``
~~~~~~~~~~~~~~~~~~~~~~~~

Las rutas ``POST`` denotan la creación de un recurso. Un ejemplo de esto es un formulario de comentarios. Vamos a utilizar la función ``mail`` para enviar un correo electrónico::

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    $app->post('/feedback', function (Request $request) {
        $message = $request->get('message');
        mail('feedback@yoursite.com', '[YourSite] Feedback', $message);

        return new Response('Thank you for your feedback!', 201);
    });

Es bastante sencillo.

.. note::

    Hay un :doc:`SwiftmailerServiceProvider <providers/swiftmailer>` incluido que puedes utilizar en lugar de ``mail()``.

La ``petición`` actual es inyectada al ``cierre`` automáticamente por *Silex* gracias al indicador de tipo. Es una instancia de `Request <http://api.symfony.com/master/Symfony/Component/HttpFoundation/Request.html>`_, por tanto puedes recuperar las variables usando el método ``get`` de la petición.

En lugar de devolver una cadena regresamos una instancia de `Response <http://api.symfony.com/master/Symfony/Component/HttpFoundation/Response.html>`_.
Esto nos permite fijar un código de estado *HTTP*, en este caso configurado a ``201 Creado``.

.. note::

    Internamente, *Silex* siempre utiliza una ``Respuesta``, la convierte a cadenas para respuestas con código de estado ``200 OK``.

Otros métodos
~~~~~~~~~~~~~

Puedes crear controladores para la mayoría de los métodos *HTTP*. Sólo tienes que llamar a uno de estos métodos en tu aplicación: ``get``, ``post``, ``put`` o ``delete``. También puedes invocar a ``match``, el cual coincidirá con todos los métodos::

    $app->match('/blog', function () {
        ...
    });

Entonces puedes restringir los métodos permitidos a través del método ``method``::

    $app->match('/blog', function () {
        ...
    })
    ->method('PATCH');

Puedes sincronizar varios métodos con un controlador utilizando la sintaxis de expresiones regulares::

    $app->match('/blog', function () {
        ...
    })
    ->method('PUT|POST');

.. note::

    El orden en que definas las rutas es importante. La primera ruta que coincida se utilizará, por lo tanto coloca tus rutas más genéricas en la parte inferior.


Variables de ruta
~~~~~~~~~~~~~~~~~

Como mostramos antes, puedes definir partes variables en una ruta, como esta:

.. code-block:: php

    $app->get('/blog/show/{id}', function ($id) {
        ...
    });

También es posible tener más de una parte variable, basta con que encierres los argumentos coincidentes con los nombres de las partes variables::

    $app->get('/blog/show/{postId}/{commentId}', function ($postId, $commentId) {
        ...
    });

Si bien no se sugiere, también lo puedes hacer (ten en cuenta la conmutación de los argumentos)::

    $app->get('/blog/show/{postId}/{commentId}', function ($commentId, $postId) {
        ...
    });

También puedes consultar la ``Petición`` actual y el objeto ``Aplicación``:

.. code-block:: php

    $app->get('/blog/show/{id}', function (Application $app, Request $request, $id) {
        ...
    });

.. note::

    Ten en cuenta que para los objetos ``Aplicación`` y  ``Petición``, *Silex* hace la inyección basándose en el indicador de tipo y no en el nombre de la variable::

        $app->get('/blog/show/{id}', function (Application $foo, Request $bar, $id) {
            ...
        });

Convertidores de variables de ruta
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Antes de inyectar las variables de ruta en el controlador, puedes aplicar algunos convertidores::

    $app->get('/user/{id}', function ($id) {
        // ...
    })->convert('id', function ($id) { return (int) $id; });

Esto es útil cuando quieres convertir las variables de ruta a objetos, ya que permite reutilizar el código de conversión entre diferentes controladores::

    $userProvider = function ($id) {
        return new User($id);
    };

    $app->get('/user/{user}', function (User $user) {
        // ...
    })->convert('user', $userProvider);

    $app->get('/user/{user}/edit', function (User $user) {
        // ...
    })->convert('user', $userProvider);

La retrollamada al convertidor también recibe la ``Petición`` como segundo argumento::

    $callback = function ($post, Request $request) {
        return new Post($request->attributes->get('slug'));
    };

    $app->get('/blog/{id}/{slug}', function (Post $post) {
        // ...
    })->convert('post', $callback);

Requisitos
~~~~~~~~~~

En algunos casos es posible que sólo desees detectar ciertas expresiones. Puedes definir los requisitos usando expresiones regulares llamando a ``assert`` en el objeto ``Controller``, que es devuelto por los métodos de enrutado.

Lo siguiente se asegurará de que el argumento ``id`` es numérico, ya que ``\d+`` coincide con cualquier cantidad de dígitos::

    $app->get('/blog/show/{id}', function ($id) {
        ...
    })
    ->assert('id', '\d+');

También puedes encadenar estas llamadas::

    $app->get('/blog/show/{postId}/{commentId}', function ($postId, $commentId) {
        ...
    })
    ->assert('postId', '\d+')
    ->assert('commentId', '\d+');

valores predeterminados
~~~~~~~~~~~~~~~~~~~~~~~

Puedes definir un valor predeterminado para cualquier variable de ruta llamando a ``value`` en el objeto ``Controlador``::

    $app->get('/{pageName}', function ($pageName) {
        ...
    })
    ->value('pageName', 'index');

Esto te permitirá coincidir ``/``, en cuyo caso la variable ``nombrePagina`` tendrá el valor de ``index``.

Rutas con nombre
~~~~~~~~~~~~~~~~

Algunos proveedores (como ``UrlGeneratorProvider``) pueden usar rutas con nombre. De manera predeterminada *Silex* generará un nombre de ruta para ti, el cual, no puedes utilizar realmente. Puedes dar un nombre a una ruta llamando a ``bind`` en el objeto ``Controlador`` devuelto por los métodos de enrutado::

    $app->get('/', function () {
        ...
    })
    ->bind('homepage');

    $app->get('/blog/show/{id}', function ($id) {
        ...
    })
    ->bind('blog_post');


.. note::

    Sólo tiene sentido nombrar rutas si utilizas proveedores que usan la ``RouteCollection``.

Filtros ``before``, ``after`` y ``finish``
------------------------------------------

*Silex* te permite ejecutar código antes e incluso después de enviada la respuesta. Esto ocurre a través de los filtros ``before``, ``after`` y ``finish``. Todo lo que necesitas hacer es pasar un cierre::

    $app->before(function () {
        // configuración
    });

    $app->after(function () {
        // destrucción
    });

    $app->finish(function () {
        // después de enviada la respuesta
    });

El filtro ``before`` tiene acceso a la ``Petición`` actual, y puede provocar un cortocircuito en toda la reproducción, devolviendo una ``Respuesta``::

    $app->before(function (Request $request) {
        // redirige al usuario al formulario de acceso si el recurso accedido está protegido
        if (...) {
            return new RedirectResponse('/login');
        }
    });

El filtro ``after`` tiene acceso a la ``Petición`` y a la ``Respuesta``:

.. code-block:: php

    $app->after(function (Request $request, Response $response) {
        // ajusta la Respuesta
    });

El filtro ``finish`` tiene acceso a la ``Petición`` y la ``Respuesta``::

    $app->finish(function (Request $request, Response $response) {
        // envía mensajes de correo electrónico ...
    });

.. note::

    Los filtros sólo los ejecuta la ``Petición`` "maestra".

Lógica intermedia para la ruta
------------------------------

Los servicios de lógica intermedia (``middlewares``) para la ruta son ejecutables *PHP* que se activan cuando coincide su ruta asociada.

* La lógica intermedia ``before`` se lanza justo antes de la retrollamada a la ruta, pero después de aplicar los filtros ``before``;

* La lógica intermedia ``after`` se activa justo después de la retrollamada a la ruta, pero antes de aplicar los filtros ``after``.

Los puedes usar en una gran cantidad de situaciones; Por ejemplo, aquí está una simple comprobación de "anónimo/usuario registrado":

.. code-block:: php

    $mustBeAnonymous = function (Request $request) use ($app) {
        if ($app['session']->has('userId')) {
            return $app->redirect('/user/logout');
        }
    };

    $mustBeLogged = function (Request $request) use ($app) {
        if (!$app['session']->has('userId')) {
            return $app->redirect('/user/login');
        }
    };

    $app->get('/user/subscribe', function () {
        ...
    })
    ->before($mustBeAnonymous);

    $app->get('/user/login', function () {
        ...
    })
    ->before($mustBeAnonymous);

    $app->get('/user/my-profile', function () {
        ...
    })
    ->before($mustBeLogged);

Los métodos ``before`` y ``after`` se pueden invocar varias veces para una ruta dada, en cuyo caso son lanzados en el mismo orden que cuando los añadiste a la
ruta.

Por comodidad, la lógica intermedia ``before`` se invoca con la instancia de la ``Petición`` actual como un argumento y la lógica intermedia ``after`` se invoca
con la ``Petición`` actual y la instancia de la ``Respuesta`` como argumentos.

Si cualquiera de las lógicas intermedias ``before`` regresa una ``Respuesta`` *HTTP* de *Symfony*, se saltará el proceso de reproducción completamente: Los siguientes servicios de lógica intermedia no se ejecutarán, ni la retrollamada a la ruta. También puedes redirigir a otra página devolviendo una respuesta de redirección, la cual puedes crear llamando al método ``redirect`` de la aplicación.

.. note::

    Si la lógica intermedia de un ``before`` no regresa una ``Respuesta`` *HTTP* de *Symfony* o ``null``, se lanza una ``RuntimeException``.

Configuración global
--------------------

Si una opción del controlador se debe aplicar a todos los controladores (un convertidor, un servicio de lógica intermedia, un requisito o un valor predeterminado), los puedes configurar en ``$application['controllers']``, que tiene todos los controladores de la aplicación::

    $app['controllers']
        ->value('id', '1')
        ->assert('id', '\d+')
        ->requireHttps()
        ->method('get')
        ->convert('id', function () { // ... })
        ->before(function () { // ... })
    ;

Estos ajustes se aplican a los controladores que ya están registrados y se convierten en los valores predefinidos para los nuevos controladores.

.. note::

    La configuración global no aplica a proveedores de controlador podrías montar tantos como tengas en tu propia configuración global (ve el párrafo sobre la modularidad más adelante).

Controladores de error
----------------------

Si alguna parte de tu código produce una excepción de la que desees mostrar algún tipo de página de error al usuario. Esto es lo que hacen los controladores de error. También los puedes utilizar para hacer cosas adicionales, tal como registrar eventos cronológicamente.

Para registrar un controlador de error, pasa un cierre al método ``error`` el cual toma un argumento ``Exception`` y devuelve una respuesta::

    use Symfony\Component\HttpFoundation\Response;

    $app->error(function (\Exception $e, $code) {
        return new Response('We are sorry, but something went terribly wrong.', $code);
    });

También puedes comprobar si hay errores específicos usando el argumento ``$code``, y manejándolo de manera diferente::

    use Symfony\Component\HttpFoundation\Response;

    $app->error(function (\Exception $e, $code) {
        switch ($code) {
            case 404:
                $message = 'The requested page could not be found.';
                break;
            default:
                $message = 'We are sorry, but something went terribly wrong.';
        }

        return new Response($message, $code);
    });

Puedes restringir un controlador de errores para que sólo maneje algunas clases de excepciones estableciendo un tipo de pista más específico para el argumento del ``Cierre``::

    $app->error(function (\LogicException $e, $code) {
        // este controlador sólo ve excepciones \LogicException
        // y \LogicException extendidas
     });

Si deseas configurar el registro cronológico de eventos, para ello, puedes utilizar un controlador de errores independiente.
Sólo asegúrate de registrarlo antes que los controladores que responden al error, porque una vez que se devuelve una respuesta, se omiten los demás controladores.

.. note::

    *Silex* viene con un proveedor para `Monolog <https://github.com/Seldaek/monolog>`_ el cual maneja el registro de errores.
    Échale un vistazo al capítulo :doc:`providers` para más detalles.

.. tip::

    *Silex* viene con un controlador de errores predeterminado que muestra un mensaje de error detallado con el seguimiento de la pila cuando **debug** es ``true``, y de otra manera un mensaje de error simple. Los manipuladores de error registrados a través del método ``error()`` siempre tienen prioridad, pero puedes mantener agradables los mensajes de error de depuración cuando se enciende con algo como esto::

        use Symfony\Component\HttpFoundation\Response;

        $app->error(function (\Exception $e, $code) use ($app) {
            if ($app['debug']) {
                return;
            }

            // lógica para manejar el error y devolver una respuesta
        });

A los manipuladores de error también se les llama cuando utilizas ``abort`` para anular tempranamente una petición::

    $app->get('/blog/show/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
        if (!isset($blogPosts[$id])) {
            $app->abort(404, "Post $id does not exist.");
        }

        return new Response(...);
    });

Redirigiendo
------------

Puedes redirigir a otra página devolviendo una respuesta de redirección, la cual puedes crear mediante una llamada al método ``redirect``::

    $app->get('/', function () use ($app) {
        return $app->redirect('/hello');
    });

Esto redirigirá de ``/`` a ``/hello``.

Reenviando
----------

Cuando quieres delegar la reproducción a otro controlador, sin una ida y vuelta al navegador (tal como una redirección), utiliza una subpetición::

    use Symfony\Component\HttpKernel\HttpKernelInterface;

    $app->get('/', function () use ($app) {
        // redirige a /hello
        $subRequest = Request::create('/hello', 'GET');

        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    });

.. tip::

    Si estás usando ``UrlGeneratorProvider``, también puedes generar la *URI*::

        $request = Request::create($app['url_generator']->generate('hello'), 'GET');

Modularidad
-----------

Cuando tu aplicación comienza a definir muchos controladores, es posible que desees agruparlos lógicamente::

    use Silex\ControllerCollection;

    // define los controladores para un blog
    $blog = new ControllerCollection();
    $blog->get('/', function () {
        return 'Blog home page';
    });
    // ...

    // define los controladores de un foro
    $forum = new ControllerCollection();
    $forum->get('/', function () {
        return 'Forum home page';
    });

    // define controladores 'globales'
    $app->get('/', function () {
        return 'Main home page';
    });

    $app->mount('/blog', $blog);
    $app->mount('/forum', $forum);

``mount()`` prefija todas las rutas con la cadena dada y las integra en la aplicación principal. Por lo tanto, ``/`` se asignará a la página principal, ``/blog/`` a la página principal del blog, y ``/forum/`` a la página principal del foro.

.. note::

    Cuando invocas a ``get()``, ``match()``, o cualquier otro método *HTTP* de la aplicación, de hecho, los estás llamando en una instancia predeterminada de ``ControllerCollection`` (almacenado en ``$app['controllers']``).

Otra ventaja es la capacidad para aplicar muy fácilmente la configuración a un conjunto de controladores. Basándonos en el ejemplo de la sección de la lógica intermedia, aquí tienes cómo protegeríamos todos los controladores para la colección ``backend``::

    $backend = new ControllerCollection();

    // garantiza que todos los controladores requieren usuarios que
    // hayan iniciado sesión
    $backend->before($mustBeLogged);

.. tip::

    Para mejorar la legibilidad, puedes dividir cada colección de controladores en un archivo independiente::

        // blog.php
        use Silex\ControllerCollection;

        $blog = new ControllerCollection();
        $blog->get('/', function () { return 'Blog home page'; });

        return $blog;

        // app.php
        $app->mount('/blog', include 'blog.php');

    En lugar de necesitar un archivo, también puedes crear un :ref:`Proveedor de controladores <proveedor-de-controladores>`.

*JSON*
------

Si quieres devolver datos *JSON*, puedes usar el método ayudante ``json``.
Simplemente le tienes que proporcionar tus datos, código de estado y cabeceras, y este creará una respuesta *JSON* para ti::

    $app->get('/users/{id}', function ($id) use ($app) {
        $user = getUser($id);

            if (!$user) {
            $error = array('message' => 'The user was not found.');
            return $app->json($error, 404);
        }

        return $app->json($user);
    });

Transmitiendo secuencias
------------------------

Es posible crear una respuesta para la transmisión de secuencias, la cual es importante en los casos cuando no puedes mantener en memoria los datos enviados::

    $app->get('/images/{file}', function ($file) use ($app) {
        if (!file_exists(__DIR__.'/images/'.$file)) {
            return $app->abort(404, 'The image was not found.');
        }

        $stream = function () use ($file) {
            readfile($file);
        };

        return $app->stream($stream, 200, array('Content-Type' => 'image/png'));
    });

Si necesitas enviar segmentos, asegúrate de llamar a ``ob_flush`` y ``flush`` después de cada parte::

    $stream = function () {
        $fh = fopen('http://www.ejemplo.com/', 'rb');
        while (!feof($fh)) {
          echo fread($fh, 1024);
          ob_flush();
          flush();
        }
        fclose($fh);
    };

Seguridad
---------

Asegúrate de proteger tu aplicación contra ataques.

Escapando
~~~~~~~~~

Cuando reproduces cualquier aportación de los usuarios (ya sea en las variables ``GET/POST`` o en variables obtenidas desde la petición), tendrás que asegurarte de escaparlas correctamente, para evitar ataques que exploten vulnerabilidades del sistema.

* **Escapando HTML**: *PHP* proporciona la función ``htmlspecialchars`` para esto.
  *Silex* ofrece un atajo, el método ``escape``::

      $app->get('/name', function (Silex\Application $app) {
          $name = $app['request']->get('name');
          return "You provided the name {$app->escape($name)}.";
      });

  Si utilizas el motor de plantillas *Twig* debes usar su escape o, incluso, mecanismos de autoescape.

* **Escapando JSON**: Si quieres proporcionar datos en formato *JSON* debes utilizar la función ``json`` de *Silex*::

      $app->get('/name.json', function (Silex\Application $app) {
          $name = $app['request']->get('name');
          return $app->json(array('name' => $name));
      });

Configurando el servidor *web*
------------------------------

Apache
~~~~~~

Si estás usando *Apache* puedes utilizar un :file:`.htaccess` para esto:

.. code-block:: apache

    <IfModule mod_rewrite.c>
        Options -MultiViews

        RewriteEngine On
        #RewriteBase /ruta/a/tu/aplicación
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [L]
    </IfModule>

.. note::

    Si tu sitio no está a nivel raíz del servidor *web*, tienes que quitar el comentario de la declaración ``RewriteBase`` y ajustar la ruta para que apunte al directorio, relativo a la raíz del servidor *web*.

Alternativamente, si utilizas *Apache* 2.2.16 o más reciente, puedes usar la `Directiva FallbackResource`_ para hacer tu :file:`.htaccess` aún más sencillo:

.. code-block:: apache

    FallbackResource index.php

``nginx``
~~~~~~~~~

Si estás utilizando ``nginx``, configura tu ``vhost`` para remitir los recursos inexistentes a ``index.php``:

.. code-block:: nginx

    server { 
        index index.php

        location / {
            try_files $uri $uri/ /index.php;
        }

        location ~ index\.php$ {
            fastcgi_pass   /var/run/php5-fpm.sock;
            fastcgi_index  index.php;
            include fastcgi_params;
        }
    }

``IIS``
~~~~~~~

Si estás utilizando el ``Internet Information Services`` de *Windows*, puedes usar como ejemplo el archivo :file:`web.config`:

.. code-block:: xml

    <?xml version="1.0"?>
    <configuration>
        <system.webServer>
            <defaultDocument>
                <files>
                    <clear />
                    <add value="index.php" />
                </files>
            </defaultDocument>
            <rewrite>
                <rules>
                    <rule name="Silex Front Controller" stopProcessing="true">
                        <match url="^(.*)$" ignoreCase="false" />
                        <conditions logicalGrouping="MatchAll">
                            <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />
                        </conditions>
                        <action type="Rewrite" url="index.php" appendQueryString="true" />
                    </rule>
                </rules>
            </rewrite>
        </system.webServer>
    </configuration>

.. _`Directiva FallbackResource`: http://www.adayinthelifeof.nl/2012/01/21/apaches-fallbackresource-your-new-htaccess-command/
.. _`descarga`: http://silex.sensiolabs.org/download

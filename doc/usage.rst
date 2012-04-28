Usándola
========

Este capítulo describe cómo utilizar *Silex*.

Arranque
--------

Para incluir *Silex* todo lo que tienes que hacer es requerir el archivo ``silex.phar`` y crear una instancia de ``Silex\Application``. Después de definir tu controlador, llama al método ``run`` en tu aplicación::

    require_once __DIR__.'/silex.phar';

    $app = new Silex\Application();

    // definiciones

    $app->run();

Otra cosa que tienes que hacer es configurar tu servidor web. Si estás usando *Apache* puedes utilizar un ``.htaccess`` para esto.

.. code-block:: apache

    <IfModule mod_rewrite.c>
        Options -MultiViews

        RewriteEngine On
        #RewriteBase /ruta/a/tu/aplicación
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [L]
    </IfModule>

.. note::

    Si tu sitio no está a nivel raíz del servidor web, tienes que descomentar la declaración ``RewriteBase`` y ajustar la ruta para que apunte al directorio, relativo a la raíz del servidor web.

.. tip::

    Cuando desarrollas un sitio web, posiblemente desees activar el modo de depuración para facilitar la corrección de errores::

        $app['debug'] = true;

.. tip::

    Si tu aplicación se encuentra detrás de un delegado inverso y deseas que *Silex* compruebe las cabeceras ``X-Forwarded-For*``, tendrás que ejecutar tu aplicación así::

        use Symfony\Component\HttpFoundation\Request;

        Request::trustProxyData();
        $app->run();

Enrutado
--------

En *Silex* defines una ruta y el controlador que se invocará cuando dicha ruta concuerde

Un patrón de ruta se compone de:

* *Pattern*: El patrón de ruta define una ruta que apunta a un recurso.
  El patrón puede incluir partes variables y tú podrás establecer los requisitos con expresiones regulares.

* *Method*: Uno de los siguientes métodos *HTTP*: ``GET``, ``POST``, ``PUT``
  ``DELETE``. Este describe la interacción con el recurso. Normalmente sólo se utilizan ``GET`` y ``POST``, pero, también es posible utilizar los otros.

El controlador se define usando un cierre de esta manera:

.. code-block:: php

    function () {
        // hace algo
    }

Los cierres son funciones anónimas que pueden importar el estado desde fuera de su definición. Esto es diferente de las variables globales, porque el estado exterior no tiene que ser global. Por ejemplo, podrías definir un cierre en una función e importar variables locales desde esa función.

.. note::

    Los cierres que no importan el ámbito se conocen como lambdas.
    Debido a que en *PHP* todas las funciones anónimas son instancias de la clase ``Closure``, no vamos a hacer una distinción aquí.

El valor de retorno del cierre se convierte en el contenido de la página.

También existe una forma alterna para definir controladores que utilizan un método de clase. La sintaxis para esto es **NombreClase**::**nombreMétodo**.
También son posibles los métodos estáticos.

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

Al visitar ``/blog`` devolverá una lista con los títulos de los comunicados en el ``blog``. La declaración ``use`` significa algo diferente en este contexto. Esta instruye al cierre a importar la variable ``comunicadosBLog`` desde el ámbito externo. Esto te permite utilizarla dentro del cierre.

Enrutado dinámico
~~~~~~~~~~~~~~~~~

Ahora, puedes crear otro controlador para ver comunicados individuales del ``blog``::

    $app->get('/blog/show/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
        if (!isset($blogPosts[$id])) {
            $app->abort(404, "Post $id does not exist.");
        }

        $post = $blogPosts[$id];

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });

Esta definición de ruta tiene una parte variable ``{id}`` que se pasa al cierre.

Cuando el comunicado no existe, estamos usando ``abort()`` para detener la petición inicial. En realidad, se produce una excepción, la cual veremos cómo manejar más adelante.

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

    *Silex* siempre utiliza internamente una ``Respuesta``, la convierte a cadenas para respuestas con código de estado ``200 OK``.

Otros métodos
~~~~~~~~~~~~~

Puedes crear controladores para la mayoría de los métodos *HTTP*. Sólo tienes que llamar uno de estos métodos en tu aplicación: ``get``, ``post``, ``put``, ``delete``. También puedes llamar ``match``, el cual coincidirá con todos los métodos::

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

Algunos proveedores (como ``UrlGeneratorProvider``) pueden usar rutas con nombre.
De manera predeterminada *Silex* generará un nombre de ruta para ti, el cual, en realidad, no puedes utilizar. Puedes dar un nombre a una ruta llamando a ``bind`` en el objeto ``Controlador`` devuelto por los métodos de enrutado::

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

Filtros ``before`` y ``after``
------------------------------

*Silex* te permite ejecutar código antes y después de cada petición. Esto ocurre a través de los filtros ``before`` y ``after``. Todo lo que necesitas hacer es pasar un cierre::

    $app->before(function () {
        // configuración
    });

    $app->after(function () {
        // destrucción
    });

El filtro ``before`` tiene acceso a la ``Petición`` actual, y puede provocar un cortocircuito en toda la reproducción, devolviendo una ``Respuesta``:

.. code-block:: php

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

.. note::

    Los filtros sólo los ejecuta la ``Petición`` "maestra".

Servicios intermedios de lógica para la ruta
--------------------------------------------

Los servicios intermedios de lógica (``middlewares``) para la ruta son ejecutables *PHP* que se desencadenan cuando encaja su ruta asociada. Se disparan justo antes de la retrollamada a la ruta, pero después de aplicar los filtros ``before``.

Los puedes usar en una gran cantidad de situaciones; Por ejemplo, aquí está una simple comprobación de "usuario anónimo/registrado":

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
    ->middleware($mustBeAnonymous);

    $app->get('/user/login', function () {
        ...
    })
    ->middleware($mustBeAnonymous);

    $app->get('/user/my-profile', function () {
        ...
    })
    ->middleware($mustBeLogged);

Una determinada ruta puede invocar varias veces a la función ``middleware``, en cuyo caso se activa en el mismo orden en que la añadas a la ruta.

Por conveniencia, las funciones de lógica intermedia de ruta se activan con la instancia de la ``Petición`` actual como su único argumento.

Si alguno de los servicios de lógica intermedia de ruta devuelve una respuesta *HTTP* de *Symfony*, cortocircuita la reproducción completa: Los siguientes servicios de lógica intermedia no se ejecutarán, ni la retrollamada a la ruta. También puedes redirigir a otra página devolviendo una respuesta de redirección, la cual puedes crear llamando al método ``redirect`` de la aplicación.

Si un servicio de lógica intermedia de ruta no devuelve una respuesta *HTTP* de *Symfony* o ``null``, se lanza una ``RuntimeException``.

Manipuladores de error
----------------------

Si alguna parte de tu código produce una excepción de la que desees mostrar algún tipo de página de error al usuario. Esto es lo que hacen los manipuladores de error. También puedes utilizarlos para hacer cosas adicionales, tal como registrar sucesos.

Para registrar un manipulador de error, pasa un cierre al método ``error`` el cual toma un argumento ``Exception`` y devuelve una respuesta:

.. code-block:: php

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

Si deseas configurar el registro puedes utilizar un manipulador de errores independiente para eso.
Sólo asegúrate de registrarlo antes que los manipuladores que responden a error, porque una vez que se devuelve una respuesta, se omiten los siguientes manipuladores.

.. note::

    *Silex* viene con un proveedor para `Monolog <https://github.com/Seldaek/monolog>`_ el cual maneja el registro de errores. Échale un vistazo al capítulo :doc:`providers` para más detalles.

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

*JSON*
------

Si quieres devolver datos *JSON*, puedes usar el método ayudante ``json``.
Simplemente le tienes que proporcionar tus datos, código de estado y cabeceras, y este creará una respuesta *JSON* para ti.

.. code-block:: php

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

Es posible crear una respuesta para la transmisión de secuencias, lo cual es importante en los casos
cuando no puedes mantener en memoria los datos que se envían.

.. code-block:: php

    $app->get('/images/{file}', function ($file) use ($app) {
        if (!file_exists(__DIR__.'/images/'.$file)) {
            return $app->abort(404, 'The image was not found.');
        }

        $stream = function () use ($file) {
            readfile($file);
        };

        return $app->stream($stream, 200, array('Content-Type' => 'image/png'));
    });

Si necesitas enviar trozos, asegúrate de llamar a ``ob_flush`` y ``flush`` después de cada parte.

.. code-block:: php

    $stream = function () {
        $fh = fopen('http://www.example.com/', 'rb');
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

Cuando reproduces la entrada del usuario (ya sea en las variables ``GET/POST`` o en variables obtenidas desde la petición), tendrás que asegurarte de escaparlas correctamente, para evitar ataques que exploten vulnerabilidades del sistema.

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

Consola
-------

*Silex* incluye una consola ligera para actualizar a la última versión.

Para saber qué versión de *Silex* estás utilizando, invoca a ``silex.phar`` en la línea de ordenes con ``version`` como argumento:

.. code-block:: text

    $ php silex.phar version
    Silex version 0a243d3 2011-04-17 14:49:31 +0200

Para comprobar si estás utilizando la última versión, ejecuta la orden ``check``:

.. code-block:: text

    $ php silex.phar check

Para actualizar ``silex.phar`` a la última versión, invoca la orden ``update``:

.. code-block:: text

    $ php silex.phar update

Esto descargará automáticamente un nuevo ``silex.phar`` desde ``silex.sensiolabs.org`` y sustituirá al actual.

Trampas
-------

Hay algunas cosas que pueden salir mal. Aquí vamos a tratar de esbozar las más frecuentes.

Configuración de *PHP*
~~~~~~~~~~~~~~~~~~~~~~

Ciertas distribuciones de *PHP*, de manera predeterminada tienen configurado ``Phar`` muy restrictivamente. Ajustar lo siguiente puede ayudar.

.. code-block:: ini

    detect_unicode = Off
    phar.readonly = Off
    phar.require_hash = Off

Si estás en ``Suhosin`` también tendrás que fijar lo siguiente:

.. code-block:: ini

    suhosin.executor.include.whitelist = phar

.. note::

    El *PHP* de *Ubuntu* viene con *Suhosin*, así que si estás usando *Ubuntu*, necesitarás este cambio.

Fallo ``Phar-Stub``
~~~~~~~~~~~~~~~~~~~

Algunas instalaciones de *PHP* tienen un error que arroja una ``PharException`` cuando tratas de incluir el ``Phar``. También te dirá que ``Silex\Application`` no se pudo encontrar. Una solución es usar la siguiente línea::

    require_once 'phar://'.__DIR__.'/silex.phar/autoload.php';

La causa exacta de esta emisión no se ha podido determinar todavía.

Fallo en el cargador de ``ioncube``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

El cargador de ``Ioncube`` es una extensión que puede decodificar archivos *PHP* codificados.
Desafortunadamente, las versiones antiguas (anteriores a la versión 4.0.9) no están funcionando bien con archivos ``phar``.
Debes actualizar tu ``Ioncube Loder`` a la versión  4.0.9  o más reciente o desactivarla comentando o eliminando esta línea en tu archivo ``php.ini``:

.. code-block:: ini

    zend_extension = /usr/lib/php5/20090626+lfs/ioncube_loader_lin_5.3.so


Configuración *IIS*
-------------------

Si estás utilizando el ``Internet Information Services`` de *Windows*, puedes utilizar de ejemplo este archivo ``web.config``:

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

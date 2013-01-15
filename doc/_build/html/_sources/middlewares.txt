Semiwares
=========

*Silex* te permite ejecutar código, que cambie el comportamiento predefinido de *Silex*, en
diferentes etapas durante la manipulación de una petición a través de *semiwares*:

* Los *semiwares de la aplicación* se desencadenan independientemente de la petición manejada actualmente;

* Los *semiwares de ruta* se desencadenan al concordar su ruta asociada.

Semiwares de la aplicación
--------------------------

Los semiwares de la aplicación sólo se ejecutan en la petición «maestra».

Semiware ``before``
~~~~~~~~~~~~~~~~~~~

Un semiware ``before`` de la aplicación te permite ajustar la ``Petición`` antes de ejecutar el
controlador::

    $app->before(function (Request $request) {
        // ...
    });

De manera predeterminada, el semiware se ejecuta después del enrutado y la seguridad.

Si quieres que tu semiware se ejecute incluso si se lanza una excepción temprana (en un error 404 o un error 403, por ejemplo), entonces, necesitas registrarlo como un evento temprano::

    $app->before(function (Request $request) {
        // ...
    }, Application::EARLY_EVENT);

Naturalmente, en este caso, el enrutado y la seguridad no se habrán ejecutado, y no tendrás acceso a la región, la ruta actual, o al usuario de seguridad.

.. note::

    El semiware ``before`` es un evento registrado en el evento ``request`` de *Symfony*.

Semiware ``after``
~~~~~~~~~~~~~~~~~~

Un semiware ``after`` de la aplicación te permite ajustar la ``Respuesta`` antes de enviarla al cliente::

    $app->after(function (Request $request, Response $response) {
        // ...
    });

.. note::

    El semiware ``after`` es un evento registrado en el evento ``request`` de *Symfony*.

semiware ``finish``
~~~~~~~~~~~~~~~~~~~

Un semiware ``finish`` de la aplicación te permite ejecutar tareas después de enviar la ``Respuesta`` al cliente (tal como enviar correo electrónico o llevar la bitácora)::

    $app->finish(function (Request $request, Response $response) {
        // ...
        // Atención: Las modificaciones a la Petición o a la Respuesta serán omitidas
    });

.. note::

    El semiware ``finish`` es un evento registrado en el evento ``terminate`` de *Symfony*.

Semiware ``route``
------------------

El semiware ``route`` se añade a las rutas o colecciones de rutas y sólo se desencadena al concordar la ruta correspondiente. También los puedes apilar::

    $app->get('/somewhere', function () {
        // ...
    })
    ->before($before1)
    ->before($before2)
    ->after($after1)
    ->after($after2)
    ;

Semiware ``before``
~~~~~~~~~~~~~~~~~~~

Un semiware de ruta ``before`` se lanza justo antes de la retrollamada a la ruta, pero después del semiware ``before`` de la aplicación::

    $before = function (Request $request) use ($app) {
        // ...
    };

    $app->get('/somewhere', function () {
        // ...
    })
    ->before($before);

Semiware ``after``
~~~~~~~~~~~~~~~~~~

Un semiware ``after`` de la ruta se lanza justo después de la retrollamada a la ruta, pero antes del semiware ``after`` de la aplicacións::

    $after = function (Request $request, Response $response) use ($app) {
        // ...
    };

    $app->get('/somewhere', function () {
        // ...
    })
    ->after($after);

Prioridad de los semiwares
--------------------------

Puedes añadir tantos semiwares como quieras, en cuyo caso serán lanzados en el mismo orden en que los añadiste.

Puedes controlar explícitamente la prioridad de tu semiware pasando un argumento adicional a los métodos suscriptores::

    $app->before(function (Request $request) {
        // ...
    }, 32);

Para tu comodidad, dos constantes te permiten registrar un evento tan temprano o tan tarde como sea posible::

    $app->before(function (Request $request) {
        // ...
    }, Application::EARLY_EVENT);

    $app->before(function (Request $request) {
        // ...
    }, Application::LATE_EVENT);

Cortocircuitando el controlador
-------------------------------

Si un semiware ``before`` regresa un objeto ``Respuesta``, se cortocircuita el controlador que está manejando la ``Petición`` (el siguiente semiware no será ejecutado, ni la retrollamada a la ruta), y la ``Respuesta`` se pasa al siguiente semiware ``after``::

    $app->before(function (Request $request) {
        // redirige al usuario al formulario de acceso si el recurso accedido está protegido
        if (...) {
            return new RedirectResponse('/login');
        }
    });

.. note::

    Si un semiware ``before`` no devuelve una ``Respuesta`` o regresa ``null``, se lanza una ``RuntimeException``.

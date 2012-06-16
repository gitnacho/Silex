``SessionServiceProvider``
==========================

El proveedor ``SessionServiceProvider`` ofrece un servicio para almacenar datos persistentes entre peticiones.

Parámetros
----------

* **session.storage.save_path** (opcional): La ruta para el ``FileSessionHandler``, de manera predeterminada es el valor de ``sys_get_temp_dir()``.

* **session.storage.options**: Un arreglo de opciones que se pasa al constructor del servicio ``session.storage``.

  En caso del `NativeSessionStorage <http://api.symfony.com/master/Symfony/Component/HttpFoundation/Session/Storage/NativeSessionStorage.html>`_, predeterminado las posibles opciones son:

  * **name**: El nombre de la ``cookie`` (por omisión ``_SESS``)
  * **id**: El ``id`` de la sesión (por omisión ``null``)
  * **cookie_lifetime**: Tiempo de vida de la ``cookie``
  * **path**: Ruta a la ``cookie``
  * **domain**: Dominio de la ``cookie``
  * **secure**: ``cookie`` segura (*HTTPS*)
  * **httponly**: Cuando la ``cookie`` únicamente es *http*

  Sin embargo, todas estas son opcionales. Las sesiones duran tanto como el navegador permanezca abierto. Para evitar esto, establece la opción ``lifetime``.

* **session.test**: Cuándo simular sesiones o no (útil cuándo escribes pruebas funcionales).

Servicios
---------

* **session**: Una instancia de la `Session <http://api.symfony.com/master/Symfony/Component/HttpFoundation/Session/Session.html>`_ de *Symfony2*.

* **session.storage**: Un servicio utilizado para persistir los datos de sesión.

* **session.storage.handler**: Un servicio que utiliza ``session.storage`` para acceder a los datos. De manera predeterminada el control de almacenamiento es `FileSessionHandler <http://api.symfony.com/master/Symfony/Component/HttpFoundation/Session/Storage/Handler/FileSessionHandler.html>`_.

Registrando
-----------

.. code-block:: php

    $app->register(new Silex\Provider\SessionServiceProvider());

Uso
---

El proveedor ``Session`` proporciona un servicio ``session``. He aquí un ejemplo que autentica a un usuario y crea una sesión para él::

    use Symfony\Component\HttpFoundation\Response;

    $app->get('/login', function () use ($app) {
        $username = $app['request']->server->get('PHP_AUTH_USER', false);
        $password = $app['request']->server->get('PHP_AUTH_PW');

        if ('igor' === $username && 'password' === $password) {
            $app['session']->start();
            $app['session']->set('user', array('username' => $username));
            return $app->redirect('/account');
        }

        $response = new Response();
        $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'site_login'));
        $response->setStatusCode(401, 'Please sign in.');
        return $response;
    });

    $app->get('/account', function () use ($app) {
        $app['session']->start();
        if (null === $user = $app['session']->get('user')) {
            return $app->redirect('/login');
        }

        return "Welcome {$user['username']}!";
    });

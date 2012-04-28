``MonologServiceProvider``
==========================

El proveedor ``MonologServiceProvider`` proporciona un mecanismo de registro predeterminado a través de la biblioteca `Monolog <https://github.com/Seldaek/monolog>`_ de Jordi Boggiano's.

Esta registrará las peticiones y errores y te permite añadir a tu aplicación el registro de depuración, para que no tengas que usar ``var_dump`` mucho más. Puedes utilizar la versión madura llamada ``tail-f``.

Parámetros
----------

* **monolog.logfile**: Archivo donde escribir los registros.

* **monolog.class_path** (opcional): Ruta a la biblioteca donde se encuentra *Monolog*.

* **monolog.level** (opcional): El nivel de registro por omisión es ``DEBUG``. Debe ser uno de ``Logger::DEBUG``, ``Logger::INFO``,
  ``Logger::WARNING``, ``Logger::ERROR``. ``DEBUG`` registra todo, ``INFO`` registrará todo excepto ``DEBUG``, etc.

* **monolog.name** (opcional): Nombre del canal de *Monplog*, por omisión es ``myapp``.

Servicios
---------

* **monolog**: La instancia del notario de *Monolog*.

  Ejemplo de uso::

    $app['monolog']->addDebug('Probando el notario de Monolog.');

* **monolog.configure**: Cierre protegido que toma al notario como argumento. Lo puedes modificar si no deseas el comportamiento por omisión.

Registrando
-----------

Asegúrate de colocar una copia de *Monolog* en el directorio ``vendor/monolog``::

    $app->register(new Silex\Provider\MonologServiceProvider(), array(
        'monolog.logfile'       => __DIR__.'/development.log',
        'monolog.class_path'    => __DIR__.'/vendor/monolog/src',
    ));

.. note::

    *Monolog* no está compilado en el archivo ``silex.phar``. Tienes que añadir tu propia copia de *Monolog* a tu aplicación.

Uso
---

El proveedor ``MonologServiceProvider`` ofrece un servicio ``monolog``. Lo puedes utilizar para agregar entradas al registro para cualquier nivel de registro a través de ``addDebug()``, ``addInfo()``, ``addWarning()`` y ``addError()``.

::

    use Symfony\Component\HttpFoundation\Response;

    $app->post('/user', function () use ($app) {
        // ...

        $app['monolog']->addInfo(sprintf("User '%s' registered.", $username));

        return new Response('', 201);
    });

Para más información, consulta la `documentación de Monolog <https://github.com/Seldaek/monolog>`_.

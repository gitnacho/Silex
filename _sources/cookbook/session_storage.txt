Cómo utilizar ``PdoSessionStorage`` para almacenar sesiones en la base de datos
===============================================================================

De manera predeterminada, el :doc:`SessionServiceProvider </providers/session>` escribe en archivos la información de sesión utilizando el ``NativeFileSessionStorage`` de *Symfony2*. La mayoría de los medianos a grandes sitios *web*, utiliza una base de datos para almacenar sesiones en lugar de archivos, porque las bases de datos son más fáciles de usar y escalar en un entorno multiservidor.

``NativeSessionStorage`` de *Symfony2* tiene múltiples soluciones de almacenamiento de sesión y una de ellas utiliza *PDO* para almacenar sesiones, ``PdoSessionHandler``.
Para usarla, reemplaza el servicio ``session.storage.handler`` en tu aplicación como se explica a continuación.

Ejemplo
-------

::

    use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

    $app->register(new Silex\Provider\SessionServiceProvider());

    $app['pdo.dsn'] = 'mysql:dbname=mydatabase';
    $app['pdo.user'] = 'myuser';
    $app['pdo.password'] = 'mypassword';

    $app['pdo.db_options'] = array(
        'db_table'      => 'session',
        'db_id_col'     => 'session_id',
        'db_data_col'   => 'session_value',
        'db_time_col'   => 'session_time',
    );

    $app['pdo'] = $app->share(function () use ($app) {
        return new PDO(
            $app['pdo.dsn'],
            $app['pdo.user'],
            $app['pdo.password']
        );
    });

    $app['session.storage.handler'] = $app->share(function () use ($app) {
        return new PdoSessionHandler(
            $app['pdo'],
            $app['pdo.db_options'],
            $app['session.storage.options']
        );
    });

Estructura de la base de datos
------------------------------

``PdoSessionStorage`` necesita una tabla en la base de datos con 3 columnas:

* ``session_id``: Columna ``ID`` (``VARCHAR(255)`` o más grande)
* ``session_value``: Valor de la columna (``TEXT`` o ``CLOB``)
* ``session_time``: La columna de tiempo (``INTEGER``)

Puedes encontrar ejemplos de declaraciones *SQL* para crear la tabla de sesión en el `Recetario
<http://gitnacho.github.com/symfony-docs-es/cookbook/configuration/pdo_session_storage.html>`_ de *Symfony2*.

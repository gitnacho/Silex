Archivo *Phar*
--------------

.. caution::

    Se desaconseja el uso del archivo ``phar`` de *Silex* En su lugar debes usar ``Composer`` para instalar *Silex* y sus dependencias o descargar uno de los archivos.

Instalando
----------

Instalar Silex es tan fácil como puedas descargar el `phar
<http://silex.sensiolabs.org/get/silex.phar>`_ y guardarlo en algún lugar en tu disco. Luego, inclúyelo en tu programa::

    <?php

    require_once __DIR__.'/silex.phar';

    $app = new Silex\Application();

    $app->get('/hello/{name}', function ($name) use ($app) {
        return 'Hello '.$app->escape($name);
    });

    $app->run();

Consola
-------

*Silex* incluye una ligera consola para actualizar a la versión más reciente.

Para saber qué versión de *Silex* estás usando, invoca a ``silex.phar`` en la línea de ordenes con ``version`` como argumento:

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

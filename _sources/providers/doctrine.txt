``DoctrineServiceProvider``
===========================

El ``DoctrineServiceProvider`` proporciona integración con el `DBAL <http://www.doctrine-project.org/projects/dbal>`_ de *Doctrine* para acceder fácilmente a la base de datos.

.. note::

    Sólo hay una ``DBAL`` de *Doctrine*. **No** se suministra un servicio ``ORM``.

Parámetros
----------

* **db.options**: Arreglo de opciones  para ``DBAL`` de *Doctrine*.

  Estas opciones están disponibles:

  * **driver**: El controlador de la base de datos a utilizar, por omisión es ``pdo_mysql``.
    Puede ser alguno de entre: ``pdo_mysql``, ``pdo_sqlite``, ``pdo_pgsql``, ``pdo_oci``, ``oci8``, ``ibm_db2``, ``pdo_ibm``, ``pdo_sqlsrv``.

  * **dbname**: El nombre de la base de datos a la cual conectarse.

  * **host**: El servidor de la base de datos a la cual conectarse. Por omisión es ``localhost``.

  * **user**: El usuario de la base de datos con el cual conectarse. Por omisión es ``root``.

  * **password**: La contraseña de la base de datos con la cual conectarse.

  * **path**: Sólo es relevante para ``pdo_sqlite``, especifica la ruta a la base de datos ``SQLite``.

  Estas y otras opciones se describen en detalle en la documentación de `configurando el DBAL de Doctrine <http://www.doctrine-project.org/docs/dbal/2.0/en/reference/configuration.html>`_.

Servicios
---------

* **db**: La conexión de base de datos, instancia de ``Doctrine\DBAL\Connection``.

* **db.config**: Objeto de configuración para *Doctrine*. El valor predeterminado es una ``Doctrine\DBAL\Configuration`` vacía.

* **db.event_manager**: Gestor de eventos para *Doctrine*.

Registrando
-----------

.. code-block:: php

    $app->register(new Silex\Provider\DoctrineServiceProvider(), array(
        'db.options' => array(
            'driver'   => 'pdo_sqlite',
            'path'     => __DIR__.'/app.db',
        ),
    ));

.. note::

    El *DBAL* de *Doctrine* viene con el archivo "gordo" de *Silex* pero no en el normal. Si  estás usando ``Composer``, añádelo como dependencia en tu archivo ``composer.json``:

    .. code-block:: javascript

        "require": {
            "doctrine/dbal": "2.2.*",
         }

Uso
---

El proveedor *Doctrine* proporciona un servicio ``db``. Aquí está un ejemplo de uso::

    $app->get('/blog/show/{id}', function ($id) use ($app) {
        $sql = "SELECT * FROM posts WHERE id = ?";
        $post = $app['db']->fetchAssoc($sql, array((int) $id));

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });

Utilizando múltiples bases de datos
-----------------------------------

El proveedor *Doctrine* te permite acceder a múltiples bases de datos. Para configurar tus fuentes de datos, sustituye ``db.options`` con ``dbs.options``.
``dbs.options`` es una matriz de configuraciones donde las claves son los nombres de las conexiones y los valores son las opciones::

    $app->register(new Silex\Provider\DoctrineServiceProvider(), array(
        'dbs.options' => array (
            'mysql_read' => array(
                'driver'    => 'pdo_mysql',
                'host'      => 'mysql_read.someplace.tld',
                'dbname'    => 'my_database',
                'user'      => 'my_username',
                'password'  => 'my_password',
            ),
            'mysql_write' => array(
                'driver'    => 'pdo_mysql',
                'host'      => 'mysql_write.someplace.tld',
                'dbname'    => 'my_database',
                'user'      => 'my_username',
                'password'  => 'my_password',
            ),
        ),
    ));

La primer conexión registrada es la predeterminada y puedes acceder a ella como lo harías si hubiera una sola conexión. Teniendo en cuenta la configuración anterior, estas dos líneas son equivalentes::

    $app['db']->fetchAssoc('SELECT * FROM table');

    $app['dbs']['mysql_read']->fetchAssoc('SELECT * FROM table');

Usando múltiples conexiones::

    $app->get('/blog/show/{id}', function ($id) use ($app) {
        $sql = "SELECT * FROM posts WHERE id = ?";
        $post = $app['dbs']['mysql_read']->fetchAssoc($sql, array((int) $id));

        $sql = "UPDATE posts SET value = ? WHERE id = ?";
        $app['dbs']['mysql_write']->execute($sql, array('newValue', (int) $id));

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });

Para más información, consulta la `Documentación del DBAL de Doctrine <http://www.doctrine-project.org/docs/dbal/2.0/en/>`_.

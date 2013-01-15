Configurando el servidor *web*
==============================

Apache
------

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

    FallbackResource /index.php

.. note::

    Si tu sitio no está a nivel raíz del servidor *web*, tienes que ajustar la ruta para que apunte a tu directorio, relativo desde el directorio web raíz.

nginx
-----

Si estás utilizando ``nginx``, configura tu ``vhost`` para remitir los recursos inexistentes a ``index.php``:

.. code-block:: nginx

    server {
        # la raíz del sitio es redirigida al guión de arranque de la aplicacion
        location = / {
            try_files @site @site;
        }

        # todas las otras ubicaciones primero prueban otros archivos y
        # van a tu controlador frontal si ninguno de ellos existe
        location / {
            try_files $uri $uri/ @site;
        }

        # devuelve 404 para todos los archivos php cuándo existe un controlador frontal
        location ~ \.php$ {
            return 404;
        }

        location @site {
            fastcgi_pass   unix:/var/run/php-fpm/www.sock;
            include fastcgi_params;
            fastcgi_param  SCRIPT_FILENAME $document_root/index.php;
            # descomentalo al ejecutarlo vía https
            #fastcgi_param HTTPS on;
        }
    }

IIS
---

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

Lighttpd
--------

Si estás usando ``lighttpd``, utiliza este sencillo ejemplo de ``host virtual`` como punto de partida:

.. code-block:: lighttpd

    server.document-root = "/ruta/a/app"

    url.rewrite-once = (
        # configura algunos archivos estáticos
        "^/assets/.+" => "$0",
        "^/favicon\.ico$" => "$0",

        "^(/[^\?]*)(\?.*)?" => "/index.php$1$2"
    )

.. _`Directiva FallbackResource`: http://www.adayinthelifeof.nl/2012/01/21/apaches-fallbackresource-your-new-htaccess-command/

*PHP* 5.4
---------

*PHP 5.4* viene con un servidor web integrado para desarrollo. Este servidor te permite ejecutar *Silex* sin ninguna configuración. Sin embargo, con el fin de servir archivos estáticos, tendrás que asegurarte de que tu controlador frontal devuelve ``false`` en ese caso::

    // web/index.php

    $filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
    if (php_sapi_name() === 'cli-server' && is_file($filename)) {
        return false;
    }

    $app = require __DIR__.'/../src/app.php';
    $app->run();


Suponiendo que el controlador frontal se encuentra en ``web/index.php``, puedes iniciar el servidor desde la línea de ordenes con la siguiente orden:

.. code-block:: text

    $ php -S localhost:8080 -t web web/index.php

Ahora la aplicación debe estar funcionando en ``http://localhost:8080``.

.. note::

    Este servidor es sólo para desarrollo. **No** se recomienda su uso en producción.

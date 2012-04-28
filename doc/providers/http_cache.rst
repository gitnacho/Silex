``HttpCacheServiceProvider``
============================

El proveedor ``HttpCacheProvider`` proporciona compatibilidad para el delegado inverso de *Symfony2*.

Parámetros
----------

* **http_cache.cache_dir**: El directorio de caché para almacenar los datos de la caché *HTTP*.

* **http_cache.options** (opcional): Una matriz de opciones para el constructor de `HttpCache <http://api.symfony.com/master/Symfony/Component/HttpKernel/HttpCache/HttpCache.html>`_.

Servicios
---------

* **http_cache**: Una instancia de `HttpCache <http://api.symfony.com/master/Symfony/Component/HttpKernel/HttpCache/HttpCache.html>`_.

Registrando
-----------

::

    $app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
        'http_cache.cache_dir' => __DIR__.'/cache/',
    ));

Uso
---

*Silex*, fuera de la caja, ya es compatible con cualquier delegado inverso como *Varnish* ajustando las cabeceras de caché *HTTP* de la Respuesta::

    $app->get('/', function() {
        return new Response('Foo', 200, array(
            'Cache-Control' => 's-maxage=5',
        ));
    });

Este proveedor te permite utilizar el delegado inverso nativo de *Symfony2* con aplicaciones *Silex* usando el servicio ``http_cache``::

    $app['http_cache']->run();

El proveedor también proporciona apoyo *ESI*::

    $app->get('/', function() {
        return new Response(<<<EOF
    <html>
        <body>
            Hello
            <esi:include src="/included" />
        </body>
    </html>

    EOF
        , 200, array(
            'Cache-Control' => 's-maxage=20',
            'Surrogate-Control' => 'content="ESI/1.0"',
        ));
    });

    $app->get('/included', function() {
        return new Response('Foo', 200, array(
            'Cache-Control' => 's-maxage=5',
        ));
    });

    $app['http_cache']->run();

Para más información, consulta la documentación de la `caché HTTP de Symfony2
<http://gitnacho.github.com/symfony-docs-es/book/http_cache.html>`_.

Cómo hacer subpeticiones
========================

Debido a que *Silex* está basado en la ``HttpKernelInterface``, te permite simular peticiones contra tu aplicación. Esto significa que puedes incorporar una página dentro de otra, también te permite reenviar una petición la cuál esencialmente es una redirección interna que no cambia la *URL*.

Fundamentos
-----------

Puedes hacer una subpetición llamando al método ``handle`` en la ``aplicación``. Este método toma tres argumentos:

* ``$request``: Una instancia de la clase ``Petición`` qué representa la petición *HTTP*.

* ``$type``: Debe ser cualquier ``HttpKernelInterface::MASTER_REQUEST`` o ``HttpKernelInterface::SUB_REQUEST``. Ciertos escuchas sólo se ejecutan en la petición maestra, así que es importante que este se ponga a ``SUB_REQUEST``.

* ``$catch``: Captura excepciones y las convierte en una respuesta con código de estado ``500``. Este argumento de manera predeterminada es ``true``. Para subpeticiones lo más probable es que lo quieras poner a ``false``.

Al llamar a ``handle``, puedes hacer una subpetición manualmente. Aquí tienes un ejemplo::

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\HttpKernelInterface;

    $subRequest = Request::create('/');
    $response = $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);

No obstante, hay algunas cosas más que necesitas tener en cuenta. En muchos casos querrás reenviar algunas partes de la petición maestra actual a la subpetición. Estas incluyen: Galletas, información del servidor, sesión.

Aquí tienes un ejemplo más avanzado que reenvía dicha información (``$request`` contiene la petición maestra)::

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\HttpKernelInterface;

    $subRequest = Request::create('/',
                                  'GET',
                                  array(),
                                  $request->cookies->all(),
                                  array(),
                                  $request->server->all()
    );
    if ($request->getSession()) {
        $subRequest->setSession($request->getSession());
    }

    $response = $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);

Para reenviar esta respuesta al cliente, sencillamente la puedes regresar desde un controlador::

    use Silex\Application;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\HttpKernelInterface;

    $app->get('/foo', function (Application $app, Request $request) {
        $subRequest = Request::create('/', ...);
        $response = $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);

        return $response;
    });

Si quieres incorporar la respuesta como parte de una página más grande puedes llamar a ``Response::getContent``::

    $header = ...;
    $footer = ...;
    $body = $response->getContent();

    return $header.$body.$footer;

Reproduciendo páginas en plantillas de *Twig*
---------------------------------------------

El :doc:`TwigServiceProvider </providers/twig>` proporciona una función ``render`` que puedes utilizar en plantillas de *Twig*. Esta te ofrece una conveniente manera de incorporar páginas.

.. code-block:: jinja

    {{ render('/sidebar') }}

Para detalles, consulta la documentación del :doc:`TwigServiceProvider </providers/twig>`.

Inclusión del borde lateral
---------------------------

Puedes utilizar cualquier *ESI* a través del :doc:`HttpCacheServiceProvider </providers/http_cache>` o un delegado inverso de caché tal como *Varnish*. Este también te permite incorporar páginas, no obstante, también te da el beneficio de memorizar partes de la página.

Este es un ejemplo de cómo debes incorporar una página vía *ESI*:

.. code-block:: jinja

    <esi:include src="/sidebar" />

Para detalles, consulta la documentación del :doc:`HttpCacheServiceProvider </providers/http_cache>`.

Tratando con la *URL* base de la petición
-----------------------------------------

Una cosa a tener en cuenta es la *URL* base. Si tu aplicación no está alojada en la raíz de tu servidor *web*, entonces puedes tener una *URL* como esta: ``http://ejemplo.org/foo/index.php/articles/42``.

En este caso, ``/foo/index.php`` es la ruta base de tu petición. *Silex* toma en cuenta este prefijo de la ruta en el proceso de enrutado, lo lee desde ``$request->server``. En el contexto de las subpeticiones esto puede conducir a problemas, porque si no prefijas la ruta base de la petición podrías cortar equivocadamente una parte de la ruta que quieres emparejar como la ruta base.

Puedes evitar que esto suceda prefijando siempre la ruta base al construir una petición::

    $url = $request->getUriForPath('/');
    $subRequest = Request::create($url,
                                  'GET',
                                  array(),
                                  $request->cookies->all(),
                                  array(),
                                  $request->server->all()
    );

Esto es algo de lo que debes estar consciente cuándo hagas subpeticiones manualmente.

Carencia de alcance del contenedor
----------------------------------

Si bien, las subpeticiones disponibles en *Silex* son bastante potentes, tienen sus límitaciones. La principal limitación/peligro en que incurrirás es la carencia de alcances en el contenedor *Pimple*.

El contenedor es un concepto que es global a una aplicación *Silex*, debido a que el objeto ``aplicación`` **es** el contenedor. Cualquier petición que se esté ejecutando contra una aplicación reutilizará el mismo conjunto de servicios. Debido a que estos servicios son mutables, el código en una petición maestra puede afectar las subpeticiones y viceversa.
Cualquier servicio que dependa del servicio ``request`` almacenará la primera petición que consiga (podría ser maestra o subpetición), y la seguirá utilizando, incluso si esa petición ya concluyó.

Por ejemplo::

    use Symfony\Component\HttpFoundation\Request;

    class ContentFormatNegotiator
    {
        private $request;

        public function __construct(Request $request)
        {
            $this->request = $request;
        }

        public function negotiateFormat(array $serverTypes)
        {
            $clientAcceptType = $this->request->headers->get('Accept');

            ...

            return $format;
        }
    }

Este ejemplo se ve inocuo, pero puede crecer. No tienes manera de saber qué regresará ``$request->headers->get()``, porque ``$request`` bien podría ser la petición maestra o una subpetición. La respuesta en este caso es pasar la petición como un argumento de ``negotiateFormat``. Luego, la puedes pasar de una ubicación donde ya tienes acceso seguro a la petición actual: un escucha o un controlador.

Aquí están unas cuantas aproximaciones generales para trabajar en torno a este problema:

* Usa *ESI* con *Varnish*.

* Nunca inyectes la petición. Usa escuchas en su lugar, ya que estos pueden acceder a la petición sin almacenarla.

* Inyecta la aplicación *Silex* y recupera la petición desde ahí.

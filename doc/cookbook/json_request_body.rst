Aceptando el cuerpo de una petición *JSON*
==========================================

Una necesidad común en la construcción de una *API* útil es la posibilidad de aceptar una entidad codificada con *JSON* desde el cuerpo de la petición.

Un ejemplo de este tipo de *API* podría ser la creación de un *blog*.

Ejemplo de *API*
----------------

En este ejemplo vamos a diseñar una *API* para crear un *blog*. La siguiente es una especificación de cómo queremos que funcione.

``Petición``
~~~~~~~~~~~~

En la petición, enviamos los datos para la entrada en el *blog* como un objeto *JSON*. También indicamos que usamos la cabecera ``Content-Type``:

.. code-block:: text

    POST /blog/posts
    Accept: application/json
    Content-Type: application/json
    Content-Length: 57

    {"title":"Hello World!","body":"This is my first post!"}

``Respuesta``
~~~~~~~~~~~~~

El servidor responde con un código de estado 201, el cual nos dice que la entrada fue creada satisfactoriamente. Diciéndonos que el ``Content-Type`` de la respuesta, también es *JSON*:

.. code-block:: text

    HTTP/1.1 201 Created
    Content-Type: application/json
    Content-Length: 65
    Connection: clocase

    {"id":"1","title":"Hello World!","body":"This is mí first post!"}

Analizando el cuerpo de la petición
-----------------------------------

El cuerpo de la petición sólo se debe analizar como *JSON* si la cabecera ``Content-Type`` comienza con ``application/json``. Como queremos hacer esto para cada petición, la solución más fácil es usar un filtro ``before``.

Simplemente usamos ``json_decode`` para analizar el contenido de la petición y luego sustituimos los datos de la petición en el objeto ``$request``::

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\ParameterBag;

    $app->before(function (Request $request) {
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }
    });

Implementando el controlador
----------------------------

Nuestro controlador creará una nueva entrada en el *blog* basada en los datos proporcionados y devolverá el objeto ``post``, incluyendo su ``id`` como *JSON*::

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    $app->post('/blog/posts', function (Request $request) {
        $post = array(
            'title' => $request->request->get('title'),
            'body'  => $request->request->get('body'),
        );

        $post['id'] = createPost($post);

        return $app->json($post, 201);
    });

Probando manualmente
--------------------

Para probar manualmente nuestra *API*, podemos usar la utilidad ``curl`` de la línea de ordenes, la cual nos permite enviar peticiones *HTTP*.

.. code-block:: bash

    $ curl http://blog.lo/blog/posts -d '{"title":"Hello World!","body":"This is my first post!"}' -H 'Content-Type: application/json'
    {"id":"1","title":"Hello World!","body":"This is my first post!"}

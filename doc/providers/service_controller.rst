``ServiceControllerServiceProvider``
====================================

Cuándo tu aplicación *Silex* crezca, posiblemente desees empezar a organizar tus controladores en una manera más formal. *Silex* puede utilizar clases de controlador fuera de la caja, pero con un poco de trabajo, tus controladores se pueden crear como servicios, dotándote de pleno poder para la inyección de dependencias y carga diferida. 

.. ::pendiente Enlace sobre las clases controlador en el recetario

¿Por qué querrías hacer esto?
-----------------------------------

- Inyección de dependencias sobre la ubicación del servicio

  Usando este método, puedes inyectar las dependencias reales requeridas por tu controlador y obtienes total inversion del control, mientras que todavía mantienes la carga diferida de tus controladores y sus dependencias. Debido a que claramente defines tus dependencias, se simulan fácilmente, permitiéndote probar tus controladores aisladamente.

- Independencia de la plataforma

  Usando este método, tus controladores comienzan a ser más independientes de la plataforma que estés utilizando. Diseñándolos cuidadosamente, tus controladores serán reutilizables en múltiples plataformas. Al mantener un cuidadoso control de tus dependencias, tus controladores fácilmente podrían ser compatibles con *Silex*, *Symfony* (la pila completa) y *Drupal*, sólo por nombrar unos cuantos. 

Parámetros
----------

Actualmente no existe ningún parámetro para el ``ServiceControllerServiceProvider``.

Servicios
---------

No proporciona servicios extra, el ``ServiceControllerServiceProvider`` simplemente extiende el servicio ``resolver`` existente.

Registrando
-----------

.. code-block:: php

    $app->register(new Silex\Provider\ServiceControllerServiceProvider());

Uso
---

En este ejemplo ideado ligeramente de la *API* de un ``blog``, vas a cambiar la ruta ``/posts.json`` para que utilice un controlador, el cual está definido como servicio.

.. code-block:: php

    use Silex\Application;
    use Demo\Repository\PostRepository;

    $app = new Application();

    $app['posts.repository'] = $app->share(function() {
        return new PostRepository;
    });

    $app->get('/posts.json', function() use ($app) {
        return $app->json($app['posts.repository']->findAll());
    });

Reescribir tu controlador como servicio es bastante sencillo, crea un objeto *PHP* plano con tu ``PostRepository`` como dependencia, junto con un método ``indexJsonAction`` para manejar la petición. A pesar de que no se muestra en el ejemplo de abajo, puedes utilizar la insinuación de tipo y parámetros nombrados para conseguir los parámetros necesarios, justo cómo con las rutas estándar de *Silex*.

Si eres seguidor de *TDD/BDD* (y deberías serlo), notarás que este controlador correctamente definió responsabilidades y dependencias, y se prueba/especifica fácilmente. También notarás que la única dependencia externa es en ``Symfony\Component\HttpFoundation\JsonResponse``, lo cual significa que este controlador fácilmente se podría utilizar en una aplicación *Symfony* (la pila completa), o potencialmente con otras aplicaciones o plataformas que saben cómo manejar un objeto ``Respuesta`` de `Symfony/HttpFoundation <http://gitnacho.github.com/symfony-docs-es/components/http_foundation/introduction.html>`_.

.. code-block:: php

    namespace Demo\Controller;

    use Demo\Repository\PostRepository;
    use Symfony\Component\HttpFoundation\JsonResponse;

    class PostController
    {
        protected $repo;

        public function __construct(PostRepository $repo)
        {
            $this->repo = $repo;
        }

        public function indexJsonAction()
        {
            return new JsonResponse($this->repo->findAll());
        }
    }

Y finalmente, define tu controlador como servicio en la aplicación, junto con tu ruta. La sintaxis en la definición de la ruta es el nombre del servicio, seguido por dos puntos (:), seguidos por el nombre del método.

.. code-block:: php

    $app['posts.controller'] = $app->share(function() use ($app) {
        return new PostController($app['posts.repository']);
    });

    $app->get('/posts.json', "posts.controller:indexJsonAction");

``SerializerServiceProvider``
=============================

El ``SerializerServiceProvider`` proporciona un servicio para serializar objetos.

Parámetros
----------

Ninguno.

Servicios
---------

* **serializer**: Una instancia de `Symfony\Component\Serializer\Serializer   <http://api.symfony.com/master/Symfony/Component/Serializer/Serializer.html>`_.

* **serializer.encoders**: `Symfony\Component\Serializer\Encoder\JsonEncoder <http://api.symfony.com/master/Symfony/Component/Serializer/Encoder/JsonEncoder.html>`_ y `Symfony\Component\Serializer\Encoder\XmlEncoder <http://api.symfony.com/master/Symfony/Component/Serializer/Encoder/XmlEncoder>`_.

* **serializer.normalizers**: `Symfony\Component\Serializer\Normalizer\CustomNormalizer <http://api.symfony.com/master/Symfony/Component/Serializer/Normalizer/CustomNormalizer>`_ y `Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer <http://api.symfony.com/master/Symfony/Component/Serializer/Normalizer/GetSetMethodNormalizer>`_.

Registrando
-----------

.. code-block:: php

    $app->register(new Silex\Provider\SerializerServiceProvider());

Uso
---

El proveedor ``SerializerServiceProvider`` proporciona un servicio ``serializer``:

.. code-block:: php

    use Silex\Application;
    use Silex\Provider\SerializerServiceProvider;
    use Symfony\Component\HttpFoundation\Response;
    
    $app = new Application();
    
    $app->register(new SerializerServiceProvider());
    
    // Sólo acepta los tipos de contenido apoyados por el
    // serializador vía el método assert.
    $app->get("/pages/{id}.{_format}", function ($id) use ($app) {
        // asume que existe el servicio page_repository que devuelve objetos Página. El
        // objeto devuelto tiene captadores y definidores que exponen el estado.
        $page = $app['page_repository']->find($id);
        $format = $app['request']->getRequestFormat();
    
        if (!$page instanceof Page) {
            $app->abort("No page found for id: $id");
        }
    
        return new Response($app['serializer']->serialize($page, $format), 200, array(
            "Content-Type" => $app['request']->getMimeType($format)
        ));
    })->assert("_format", "xml|json")
      ->assert("id", "\d+");


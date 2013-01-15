``SymfonyBridgesServiceProvider``
=================================

El *SymfonyBridgesServiceProvider* proporciona integración adicional entre componentes y bibliotecas de *Symfony2*.

Parámetros
----------

* **symfony_bridges.class_path** (opcional): Ruta a la ubicación donde están localizados los puentes de *Symfony2*.

*Twig*
------

Cuando está activado el ``SymfonyBridgesServiceProvider``, el ``TwigServiceProvider`` te proporcionará capacidades adicionales:

* **UrlGeneratorServiceProvider**: Si estás usando el ``UrlGeneratorServiceProvider``, recibirás los ayudantes ``path`` y ``url`` para *Twig*. Puedes encontrar más información en la documentación de `enrutado de Symfony2 <http://gitnacho.github.com/symfony-docs-es/book/routing.html#generando-url-desde-una-plantilla>`_.

* **TranslationServiceProvider**: Si estás usando el ``TranslationServiceProvider``, recibirás los ayudantes ``trans`` y ``transchoice`` para traducir en las plantillas ``Twig``. Puedes encontrar más información en la documentación de `traducción de Symfony2 <http://gitnacho.github.com/symfony-docs-es/book/translation.html#plantillas-twig>`_.

* **FormServiceProvider**: Si estás usando el ``FormServiceProvider``, recibirás un conjunto de ayudantes para trabajar con formularios en plantillas.
  Puedes encontrar más información en la `referencia de formularios de Symfony2 <http://gitnacho.github.com/symfony-docs-es/reference/forms/twig_reference.html>`_.

Registrando
-----------

Asegúrate de colocar una copia del puente *Symfony2* en ``vendor/symfony/src`` bien clonando `Symfony2 <https://github.com/symfony/symfony>`_ o
``vendor/symfony/src/Symfony/Bridge/Twig`` clonando `TwigBridge <https://github.com/symfony/TwigBridge>`_
(el último consume menos recursos).

Luego, registra el proveedor vía::

    $app->register(new Silex\Provider\SymfonyBridgesServiceProvider(), array(
        'symfony_bridges.class_path'  => __DIR__.'/vendor/symfony/src',
    ));

``TranslationServiceProvider``
==============================

El ``TranslationServiceProvider`` provee un servicio para traducir tu aplicación a diferentes idiomas.

Parámetros
----------

* **translator.messages**: Una asignación de regiones para los arreglos de mensajes. Este parámetro contiene los datos de traducción en todos los idiomas.

* **locale** (opcional): La región para el traductor. Lo más probable es que desees establecer este basándote en algún parámetro de la petición. Predeterminada a ``en``.

* **locale_fallback** (opcional): La región de reserva para el traductor. Esta se utiliza cuando la configuración regional actual no tiene ningún conjunto de mensajes.

* **translation.class_path** (opcional): Ruta a donde se encuentra el componente ``Translation`` de *Symfony2*.

Servicios
---------

* **translator**: Una instancia de `Translator <http://api.symfony.com/master/Symfony/Component/Translation/Translator.html>`_, utilizada para traducir.

* **translator.loader**: Una instancia de una implementación de la
  `LoaderInterface <http://api.symfony.com/master/Symfony/Component/Translation/Loader/LoaderInterface.html>`_ del traductor, predeterminada a un `ArrayLoader <http://api.symfony.com/master/Symfony/Component/Translation/Loader/ArrayLoader.html>`_.

* **translator.message_selector**: Una instancia de `MessageSelector <http://api.symfony.com/master/Symfony/Component/Translation/MessageSelector.html>`_.

Registrando
-----------

Asegúrate de colocar una copia del componente de traducción de *Symfony2* en ``vendor/symfony/src``. Puedes simplemente clonar todo *Symfony2* en ``vendor``::

    $app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'locale_fallback'           => 'en',
        'translation.class_path'    => __DIR__.'/vendor/symfony/src',
    ));

Uso
---

El proveedor ``Translation`` ofrece un servicio ``traductor`` y usa el parámetro ``translator.messages``::

    $app['translator.messages'] = array(
        'en' => array(
            'hello'     => 'Hello %name%',
            'goodbye'   => 'Goodbye %name%',
        ),
        'de' => array(
            'hello'     => 'Hallo %name%',
            'goodbye'   => 'Tschüss %name%',
        ),
        'fr' => array(
            'hello'     => 'Bonjour %name%',
            'goodbye'   => 'Au revoir %name%',
        ),
    );

    $app->before(function () use ($app) {
        if ($locale = $app['request']->get('locale')) {
            $app['locale'] = $locale;
        }
    });

    $app->get('/{locale}/{message}/{name}', function ($message, $name) use ($app) {
        return $app['translator']->trans($message, array('%name%' => $name));
    });

El ejemplo anterior se traducirá en las siguientes rutas:

* ``/en/hello/igor`` regresará ``Hello igor``.

* ``/de/hello/igor`` regresará ``Hallo igor``.

* ``/fr/hello/igor`` regresará ``Bonjour igor``.

* ``/it/hello/igor`` regresará ``Hello igor`` (debido a la reserva).

Recetas
-------

Archivos de idioma basados en *YAML*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Tener tu traducción en archivos *PHP* puede ser un inconveniente. Esta receta te muestra cómo cargar traducciones de archivos *YAML* externos.

En primer lugar necesitas los componentes ``Config`` y ``Yaml`` de *Symfony2*. Además, asegúrate de registrarlos en el cargador automático. Puedes clonar el repositorio de *Symfony2* completo en ``vendor/symfony``::

    $app['autoloader']->registerNamespace('Symfony', __DIR__.'/vendor/symfony/src');

A continuación, debes crear las asignaciones de idioma en los archivos *YAML*. Un nombre que puedes utilizar es ``locales/en.yml``. Sólo haz la asignación en este archivo de la siguiente manera:

.. code-block:: yaml

    hello: Hello %name%
    goodbye: Goodbye %name%

Repite esto para todos tus idiomas. A continuación, configura el ``translator.messages`` para asignar archivos a los idiomas:

.. code-block:: php

    $app['translator.messages'] = array(
        'en' => __DIR__.'/locales/en.yml',
        'de' => __DIR__.'/locales/de.yml',
        'fr' => __DIR__.'/locales/fr.yml',
    );

Finalmente sobrescribe el ``translator.loader`` para utilizar ``YamlFileLoader`` en lugar del ``ArrayLoader`` predeterminado:

.. code-block:: php


    use Symfony\Component\Translation\Loader\YamlFileLoader;

    $app['translator.loader'] = $app->share(function () {
        return new YamlFileLoader();
    });

Y eso es todo lo que necesitas para cargar traducciones desde archivos *YAML*.

Archivos de idioma basados en *YAML*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Al igual que lo harías con los archivos de traducción *YAML*, primero tienes que asegurarte de que tienes el componente ``Config`` de *Symfony2*, y haberlo registrado en el cargador automático. Ve arriba para más detalles.

Luego, del mismo modo, crea los archivos *XLIFF* en tu directorio de regiones y configurar la opción ``translator.messages`` para asignarlos.

Finalmente redefine el ``translator.loader`` para utilizar un ``XliffFileLoader``::

    use Symfony\Component\Translation\Loader\XliffFileLoader;

    $app['translator.loader'] = $app->share(function () {
        return new XliffFileLoader();
    });

¡Eso es todo!

Accediendo a las traducciones en las plantillas *Twig*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Una vez cargado, el proveedor del servicio de traducción está disponible desde las plantillas *Twig*:

.. code-block:: jinja
    {{ app.translator.trans('translation_key') }}

Aún mejor: registra el :doc:`SymfonyBridgesServiceProvider <providers/symfony_bridges>` y obtendrás la ``TranslationExtension`` del puente, misma que te permite traducir cadenas a la manera de *Twig*:

.. code-block:: jinja
    {{ 'translation_key'|trans }}
    {{ 'translation_key'|transchoice }}
    {% trans %}translation_key{% endtrans %}

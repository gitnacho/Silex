``TranslationServiceProvider``
==============================

El ``TranslationServiceProvider`` provee un servicio para traducir tu aplicación a diferentes idiomas.

Parámetros
----------

* **translator.domains**: Una asignación de dominios/regiones/mensajes. Este parámetro contiene los datos traducidos de todos los dominios e idiomas.

* **locale** (opcional): La región para el traductor. Lo más probable es que desees establecer este basándote en algún parámetro de la petición. Predeterminada a ``en``.

* **locale_fallback** (opcional): La región de reserva para el traductor. Esta se utiliza cuando la configuración regional actual no tiene ningún conjunto de mensajes.

Servicios
---------

* **translator**: Una instancia de `Translator <http://api.symfony.com/master/Symfony/Component/Translation/Translator.html>`_, utilizada para traducir.

* **translator.loader**: Una instancia de una implementación de la
  `LoaderInterface <http://api.symfony.com/master/Symfony/Component/Translation/Loader/LoaderInterface.html>`_ del traductor, predeterminada a un `ArrayLoader <http://api.symfony.com/master/Symfony/Component/Translation/Loader/ArrayLoader.html>`_.

* **translator.message_selector**: Una instancia de `MessageSelector <http://api.symfony.com/master/Symfony/Component/Translation/MessageSelector.html>`_.

Registrando
-----------

.. code-block:: php

    $app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'locale_fallback' => 'en',
    ));

.. note::

    El componente de traducción de *Symfony* viene en el archivo "gordo" de *Silex* pero no en el normal. Si estás usando ``Composer``, añádelo como dependencia a tu archivo ``composer.json``:

    .. code-block:: json

        "require": {
            "symfony/translation": "2.1.*"
        }

Uso
---

El proveedor ``Translation`` ofrece un servicio ``traductor`` y usa el parámetro ``translator.domains``::

    $app['translator.domains'] = array(
        'messages' => array(
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
        ),
        'validators' => array(
            'fr' => array(
                'This value should be a valid number.' => 'Cette valeur doit être un nombre.',
            ),
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

En primer lugar, añade los componentes ``Config`` y ``Yaml`` a tu archivo composer.

.. code-block:: json

    "require": {
        "symfony/config": "2.1.*",
        "symfony/yaml": "2.1.*",
    }

A continuación, debes crear las asignaciones de idioma en los archivos *YAML*. Un nombre que puedes utilizar es ``locales/en.yml``. Sólo haz la asignación en este archivo de la siguiente manera:

.. code-block:: yaml

    hello: Hello %name%
    goodbye: Goodbye %name%

Repite esto para todos tus idiomas. A continuación, configura el ``translator.domains`` para asignar idiomas a los archivos::

    $app['translator.domains'] = array(
        'messages' => array(
            'en' => __DIR__.'/locales/en.yml',
            'de' => __DIR__.'/locales/de.yml',
            'fr' => __DIR__.'/locales/fr.yml',
        ),
    );

Finalmente sobrescribe el ``translator.loader`` para utilizar ``YamlFileLoader`` en lugar del ``ArrayLoader`` predeterminado:

.. code-block:: php

    use Symfony\Component\Translation\Loader\YamlFileLoader;

    $app['translator.loader'] = $app->share(function () {
        return new YamlFileLoader();
    });

Y eso es todo lo que necesitas para cargar traducciones desde archivos *YAML*.

Archivos de idioma basados en *XLIFF*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Al igual que lo harías con los archivos de traducción *YAML*, primero te tienes que asegurar de que tienes como dependencia el componente ``Config`` de *Symfony2* (ve los detalles arriba).

Luego, del mismo modo, crea los archivos *XLIFF* en tu directorio de regiones y configura la opción ``translator.domains`` para asignarlos.

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

Además, cuándo utilizas el puente *Twig* proporcionado por *Symfony* (ve :doc:`TwigServiceProvider </providers/twig>`), te será permitido traducir cadenas a la manera de *Twig*:

.. code-block:: jinja

    {{ 'translation_key'|trans }}
    {{ 'translation_key'|transchoice }}
    {% trans %}translation_key{% endtrans %}

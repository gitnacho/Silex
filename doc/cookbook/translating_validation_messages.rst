Traduciendo mensajes de validación
==================================

Cuando trabajas con la validador de *Symfony2*, una tarea común sería mostrar mensajes de validación traducidos.

Para ello, tendrás que registrar el traductor y apuntar a los recursos traducidos:

::

    $app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'locale' => 'sr_Latn',
        'translation.class_path' => __DIR__ . '/vendor/symfony/src',
        'translator.messages' => array()
    ));
    $app->before(function () use ($app) {
        $app['translator']->addLoader('xlf', new Symfony\Component\Translation\Loader\XliffFileLoader());
        $app['translator']->addResource('xlf', __DIR__ . '/vendor/symfony/src/Symfony/Bundle/FrameworkBundle/Resources/translations/validators.sr_Latn.xlf', 'sr_Latn', 'validators');
    });

Y eso es todo lo que necesitas para cargar las traducciones desde archivos ``xlf`` a *Symfony2*
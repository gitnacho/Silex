``ValidatorServiceProvider``
============================

El ``ValidatorServiceProvider`` ofrece un servicio de validación de datos. Es más útil cuando lo utilizas con ``FormServiceProvider``, pero también se puede utilizar de manera independiente.

Parámetros
----------

* **validator.class_path** (opcional): Ruta a donde se encuentra el componente ``Validator`` de *Symfony2*.

Servicios
---------

* **validator**: Una instancia del `Validador <http://api.symfony.com/master/Symfony/Component/Validator/Validator.html>`_.

* **validator.mapping.class_metadata_factory**: Fábrica de cargadores de metadatos, que pueden leer la información de validación desde la restricción de las clases. El valor predeterminado es ``StaticMethodLoader--ClassMetadataFactory``.

  Esto significa que puedes definir un método estático ``loadValidatorMetadata`` en tu clase de datos, que tenga un argumento ``ClassMetadata``. Entonces puedes establecer restricciones en esta instancia de ``ClassMetadata``.

* **validator.validator_factory**: Fábrica de ``ConstraintValidators``. De manera predeterminada a un ``ConstraintValidatorFactory`` estándar. Generalmente lo utiliza internamente el validador.

Registrando
-----------

Asegúrate de colocar una copia del componente ``Validator`` de *Symfony2* en ``vendor/symfony/src``. Puedes simplemente clonar todo *Symfony2* en ``vendor``::

    $app->register(new Silex\Provider\ValidatorServiceProvider(), array(
        'validator.class_path'    => __DIR__.'/vendor/symfony/src',
    ));

Uso
---

El proveedor ``Validator`` proporciona un servicio ``validator``.

Validando valores
~~~~~~~~~~~~~~~~~

Puedes validar directamente los valores usando el método de validación ``validateValue``::

    use Symfony\Component\Validator\Constraints;

    $app->get('/validate-url', function () use ($app) {
        $violations = $app['validator']->validateValue($app['request']->get('url'), new Constraints\Url());
        return $violations;
    });

Esto está limitado relativamente.

Validando propiedades de objeto
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Si deseas añadir validaciones a una clase, puedes implementar un método estático ``loadValidatorMetadata`` como se describe en *Servicios*. Esto te permite definir las restricciones para las propiedades de tu objeto. También trabaja con captadores::

    use Symfony\Component\Validator\Mapping\ClassMetadata;
    use Symfony\Component\Validator\Constraints;

    class Post
    {
        public $title;
        public $body;

        static public function loadValidatorMetadata(ClassMetadata $metadata)
        {
            $metadata->addPropertyConstraint('title', new Constraints\NotNull());
            $metadata->addPropertyConstraint('title', new Constraints\NotBlank());
            $metadata->addPropertyConstraint('body', new Constraints\MinLength(array('limit' => 10)));
        }
    }

    $app->post('/posts/new', function () use ($app) {
        $post = new Post();
        $post->title = $app['request']->get('title');
        $post->body = $app['request']->get('body');

        $violations = $app['validator']->validate($post);
        return $violations;
    });

Tendrás que manipular la presentación de estas violaciones tú mismo. No obstante, puedes utilizar el ``FormServiceProvider`` el cual puede usar el ``ValidatorServiceProvider``.

Para más información, consulta la documentación de `validación de Symfony2 <http://symfony.com/doc/2.0/book/validation.html>`_.

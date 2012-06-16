``ValidatorServiceProvider``
============================

El ``ValidatorServiceProvider`` ofrece un servicio de validación de datos. Es más útil cuando lo utilizas con ``FormServiceProvider``, pero también se puede utilizar de manera independiente.

Parámetros
----------

ninguno

Servicios
---------

* **validator**: Una instancia del `Validador <http://api.symfony.com/master/Symfony/Component/Validator/Validator.html>`_.

* **validator.mapping.class_metadata_factory**: Fábrica de cargadores de metadatos, que pueden leer la información de validación desde la restricción de las clases. El valor predeterminado es ``StaticMethodLoader--ClassMetadataFactory``.

  Esto significa que puedes definir un método estático ``loadValidatorMetadata`` en tu clase de datos, que tenga un argumento ``ClassMetadata``. Entonces puedes establecer restricciones en esta instancia de ``ClassMetadata``.

* **validator.validator_factory**: Fábrica de ``ConstraintValidators``. De manera predeterminada a un ``ConstraintValidatorFactory`` estándar. Generalmente lo utiliza internamente el validador.

Registrando
-----------

.. code-block:: php

    $app->register(new Silex\Provider\ValidatorServiceProvider());

.. note::

    El componente validator de *Symfony* viene en el archivo "gordo" de *Silex* pero no en el normal. Si estás usando ``Composer``, añádelo como dependencia a tu archivo ``composer.json``:

    .. code-block:: json

        "require": {
            "symfony/validator": "2.1.*"
        }

Uso
---

El proveedor ``Validator`` proporciona un servicio ``validator``.

Validando valores
~~~~~~~~~~~~~~~~~

Puedes validar directamente los valores usando el método de validación ``validateValue``::

    use Symfony\Component\Validator\Constraints as Assert;

    $app->get('/validate/{email}', function ($email) use ($app) {
        $errors = $app['validator']->validateValue($email, new Assert\Email());

        if (count($errors) > 0) {
            return (string) $errors;
        } else {
            return 'The email is valid';
        }
    });

Validando arreglos
~~~~~~~~~~~~~~~~~~

.. code-block:: php

    use Symfony\Component\Validator\Constraints as Assert;

    class Book
    {
        public $title;
        public $author;
    }

    class Author
    {
        public $first_name;
        public $last_name;
    }

    $book = array(
        'title' => 'My Book',
        'author' => array(
            'first_name' => 'Fabien',
            'last_name'  => 'Potencier',
        ),
    );

    $constraint = new Assert\Collection(array(
        'title' => new Assert\MinLength(10),
        'author' => new Assert\Collection(array(
            'first_name' => array(new Assert\NotBlank(), new Assert\MinLength(10)),
            'last_name'  => new Assert\MinLength(10),
        )),
    ));
    $errors = $app['validator']->validateValue($book, $constraint);

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            echo $error->getPropertyPath().' '.$error->getMessage()."\n";
        }
    } else {
        echo 'The book is valid';
    }

Validando objetos
~~~~~~~~~~~~~~~~~

Si quiere añadir validación a una clase, puedes definir restricciones y captadores para las propiedades de la clase, y luego llamar al método ``validate``::

    use Symfony\Component\Validator\Constraints as Assert;

    $author = new Author();
    $author->first_name = 'Fabien';
    $author->last_name = 'Potencier';

    $book = new Book();
    $book->title = 'My Book';
    $book->author = $author;

    $metadata = $app['validator.mapping.class_metadata_factory']->getClassMetadata('Author');
    $metadata->addPropertyConstraint('first_name', new Assert\NotBlank());
    $metadata->addPropertyConstraint('first_name', new Assert\MinLength(10));
    $metadata->addPropertyConstraint('last_name', new Assert\MinLength(10));

    $metadata = $app['validator.mapping.class_metadata_factory']->getClassMetadata('Book');
    $metadata->addPropertyConstraint('title', new Assert\MinLength(10));
    $metadata->addPropertyConstraint('author', new Assert\Valid());

    $errors = $app['validator']->validate($book);

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            echo $error->getPropertyPath().' '.$error->getMessage()."\n";
        }
    } else {
        echo 'The author is valid';
    }

También puede declarar la restricción de clase añadiendo un método estático ``loadValidatorMetadata`` a tus clases::

    use Symfony\Component\Validator\Mapping\ClassMetadata;
    use Symfony\Component\Validator\Constraints as Assert;

    class Book
    {
        public $title;
        public $author;

        static public function loadValidatorMetadata(ClassMetadata $metadata)
        {
            $metadata->addPropertyConstraint('title', new Assert\MinLength(10));
            $metadata->addPropertyConstraint('author', new Assert\Valid());
        }
    }

    class Author
    {
        public $first_name;
        public $last_name;

        static public function loadValidatorMetadata(ClassMetadata $metadata)
        {
            $metadata->addPropertyConstraint('first_name', new Assert\NotBlank());
            $metadata->addPropertyConstraint('first_name', new Assert\MinLength(10));
            $metadata->addPropertyConstraint('last_name', new Assert\MinLength(10));
        }
    }

    $app->get('/validate/{email}', function ($email) use ($app) {
        $author = new Author();
        $author->first_name = 'Fabien';
        $author->last_name = 'Potencier';

        $book = new Book();
        $book->title = 'My Book';
        $book->author = $author;

        $errors = $app['validator']->validate($book);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                echo $error->getPropertyPath().' '.$error->getMessage()."\n";
            }
        } else {
            echo 'The author is valid';
        }
    });

.. note::

    Usa ``addGetterConstraint()`` para añadir restricciones en los métodos captadores y ``addConstraint()`` para agregar restricciones a la propia clase.

Traducción
~~~~~~~~~~

Para poder traducir los mensajes de error, puedes usar el proveedor de traducción y registrar los mensajes bajo el dominio ``validators``::

    $app['translator.domains'] = array(
        'validators' => array(
            'fr' => array(
                'This value should be a valid number.' => 'Cette valeur doit être un nombre.',
            ),
        ),
    );

Para más información, consulta la documentación de `validación de Symfony2 <http://gitnacho.github.com/symfony-docs-es/book/validation.html>`_.

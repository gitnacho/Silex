Cómo usar *YAML* para configurar la validación
==============================================

La simplicidad es el corazón de *Silex* por lo tanto la solución no está fuera de la caja para usar archivos *YAML* en la validación. Pero esto no significa que eso no sea posible. Vamos a ver cómo hacerlo.

Primero, necesitas instalar el componente *YAML*. Declarándolo como una dependencia en tu archivo ``composer.json``:

.. code-block:: json

    "require": {
        "symfony/yaml": "2.1.*"
    }

Después, necesitas decirle al Servicio de validación que no estás usando ``StaticMethodLoader`` para cargar los metadatos de tu clase, sino un archivo *YAML*::

    $app->register(new ValidatorServiceProvider());

    $app['validator.mapping.class_metadata_factory'] = new Symfony\Component\Validator\Mapping\ClassMetadataFactory(
        new Symfony\Component\Validator\Mapping\Loader\YamlFileLoader(__DIR__.'/validation.yml')
    );

Ahora, podemos sustituir el uso del método estático y mover todas las reglas de validación a ``validation.yml``:

.. code-block:: yaml

    # validation.yml
    Post:
      properties:
        title:
          - NotNull: ~
          - NotBlank: ~
        body:
          - Min: 100

``FormServiceProvider``
=======================

El ``FormServiceProvider`` proporciona un servicio para construir formularios en tu aplicación con el componente ``Form`` de *Symfony2*.

Parámetros
----------

* **form.secret**: Esto el valor secreto se utiliza para generar y validar el fragmento ``CSRF`` para una página específica. Es muy importante para ti para ajustar este valor a un valor estático generado aleatoriamente, para impedir el secuestro de tus formularios. Por omisión es ``md5(__DIR__)``.

Servicios
---------

* **form.factory**: Una instancia de `FormFactory <http://api.symfony.com/master/Symfony/Component/Form/FormFactory.html>`_, usada para construir un formulario.

* **form.csrf_provider**: Una instancia de una implementación del `CsrfProviderInterface <http://api.symfony.com/master/Symfony/Component/Form/Extension/Csrf/CsrfProvider/CsrfProviderInterface.html>`_, de manera predeterminada es `DefaultCsrfProvider <http://api.symfony.com/master/Symfony/Component/Form/Extension/Csrf/CsrfProvider/DefaultCsrfProvider.html>`_.

Registrando
-----------

.. code-block:: php

    use Silex\Provider\FormServiceProvider;

    $app->register(new FormServiceProvider());

.. note::

    El Componente ``Form`` de *Symfony* viene con el archivo "gordo" de *Silex* pero no en el normal. Si  estás usando ``Composer``, añádelo como dependencia en tu archivo ``composer.json``:

    .. code-block:: json

        "require": {
            "symfony/form": "2.1.*"
        }

Uso
---

El ``FormServiceProvider`` proporciona un servicio ``form.factory``. Aquí está un ejemplo de uso::

    $app->match('/form', function (Request $request) use ($app) {
        // algún dato predefinido para cuando se muestra el formulario por primera vez
        $data = array(
            'name' => 'Your name',
            'email' => 'Your email',
        );

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add('name')
            ->add('email')
            ->add('gender', 'choice', array(
                'choices' => array(1 => 'male', 2 => 'female'),
                'expanded' => true,
            ))
            ->getForm();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                // hace algo con los datos

                // redirige a algún lugar
                return $app->redirect('...');
            }
        }

        // muestra el formulario
        return $app['twig']->render('index.twig', array('form' => $form->createView()));
    });

Y aquí tienes la plantilla del formulario ``index.twig``:

.. code-block:: jinja

    <form action="#" method="post">
        {{ form_widget(form) }}

        <input type="submit" name="submit" />
    </form>

Si estás usando el proveedor de validación, también puedes agregar validación a tu formulario añadiendo restricciones en los campos::

    use Symfony\Component\Validator\Constraints as Assert;

    $app->register(new Silex\Provider\ValidatorServiceProvider());
    $app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'translator.messages' => array(),
    ));

    $form = $app['form.factory']->createBuilder('form')
        ->add('name', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(5))
        ))
        ->add('email', 'text', array(
            'constraints' => new Assert\Email()
        ))
        ->add('gender', 'choice', array(
            'choices' => array(1 => 'male', 2 => 'female'),
            'expanded' => true,
            'constraints' => new Assert\Choice(array(1, 2)),
        ))
        ->getForm();

Para más información, consulta la `documentación de Formularios de Symfony2 <http://gitnacho.github.com/symfony-docs-es/book/forms.html>`_.

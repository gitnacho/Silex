Desactivando la protección *CSRF* en un formulario utilizando la ``FormExtension``
==================================================================================

*FormExtension* ofrece un servicio para construir formularios en tu aplicación con el componente ``Form`` de *Symfony2*. Por omisión, *FormExtension* utiliza la protección *CSRF* para evitar la falsificación de peticiones en sitios cruzados, un método por el cual un usuario malintencionado intenta hacer que tus usuarios legítimos envíen datos que no tienen la intención de presentar.

Puedes encontrar más detalles sobre la Protección *CSRF* y el fragmento *CSRF* en el `Libro de Symfony2 <http://gitnacho.github.com/symfony-docs-es/book/forms.html#proteccion-csrf>`_

En algunos casos (por ejemplo, al integrar un formulario en un mensaje de correo *html*) es posible que no quieras utilizar esta protección. La forma más sencilla de evitarlo es entender que es posible dar opciones específicas a tu creador de formularios a través de la función ``createBuilder()``.

Ejemplo
-------

.. code-block:: php

    $form = $app['form.factory']->createBuilder('form', null, array('csrf_protection' => false));

Eso es todo, tu formulario se puede enviar desde cualquier parte sin protección *CSRF*.

Prosiguiendo
------------

Este ejemplo en concreto muestra cómo cambiar la ``csrf_protection`` en el parámetro ``$options`` de la función ``createBuilder()``. Podrías pasar más a través de este parámetro, es tan simple como usar el método ``getDefaultOptions()`` de *Symfony2* en tus clases formulario. `Ve más aquí <http://gitnacho.github.com/symfony-docs-es/book/forms.html#creando-clases-form>`_.

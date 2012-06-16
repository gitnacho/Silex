``SwiftmailerServiceProvider``
==============================

El proveedor ``SwiftmailerServiceProvider`` ofrece un servicio para enviar correo electrónico a través de la biblioteca de correo `Swift Mailer <http://swiftmailer.org>`_.

Puedes utilizar el servicio ``mailer`` (cliente de correo) para enviar mensajes fácilmente. Por omisión, este tratará de enviar el correo electrónico a través de *SMTP*.

Parámetros
----------

* **swiftmailer.options**: Un arreglo de opciones para la configuración predeterminada basada en *SMTP*.

  Puedes ajustar las siguientes opciones:

  * **host**: nombre del servidor *SMTP*, predeterminado a ``'localhost'``.
  * **port**: puerto *SMTP*, el predeterminado es 25.
  * **username**: nombre del usuario *SMTP*, por omisión es una cadena vacía.
  * **password**: contraseña *SMTP*, de manera predeterminada es una cadena vacía.
  * **encryption**: cifrado *SMTP*, predeterminado a ``null``.
  * **auth_mode**: modo de autenticación *SMTP*, predeterminado a ``null``.

* **swiftmailer.class_path** (opcional): Ruta a donde está ubicada la librería ``Swift Mailer``.

Servicios
---------

* **mailer**: La instancia del cliente de correo.

  Ejemplo de uso::

    $message = \Swift_Message::newInstance();

    // ...

    $app['mailer']->send($message);

* **swiftmailer.transport**: El transporte usado para entregar el correo electrónico. Predeterminado a ``Swift_Transport_EsmtpTransport``.

* **swiftmailer.transport.buffer**: El ``StreamBuffer`` usado por el transporte.

* **swiftmailer.transport.authhandler**: Controlador de autenticación usado por el transporte. De manera predeterminada intentará con los siguientes: CRAM-MD5, login, plaintext.

* **swiftmailer.transport.eventdispatcher**: Despachador de eventos interno usado por ``Swiftmailer``.

Registrando
-----------

.. code-block:: php

    $app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
        'swiftmailer.class_path'  => __DIR__.'/vendor/swiftmailer/swiftmailer/lib/classes',
    ));

.. note::

    ``SwiftMailer`` viene con el archivo "gordo" de *Silex* pero no en el normal. Si  estás usando ``Composer``, añádelo como dependencia en tu archivo ``composer.json``:

    .. code-block:: javascript

        "require": {
            "swiftmailer/swiftmailer": ">=4.1.2,<4.2-dev"
        }

Uso
---

El proveedor ``Swiftmailer`` proporciona un servicio ``mailer`` o cliente de correo::

    $app->post('/feedback', function () use ($app) {
        $request = $app['request'];

        $message = \Swift_Message::newInstance()
            ->setSubject('[YourSite] Feedback')
            ->setFrom(array('noreply@yoursite.com'))
            ->setTo(array('feedback@yoursite.com'))
            ->setBody($request->get('message'));

        $app['mailer']->send($message);

        return new Response('Thank you for your feedback!', 201);
    });

Para más información, consulta la `Documentación de Swift Mailer
<http://swiftmailer.org>`_.

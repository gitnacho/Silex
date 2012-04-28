Funcionamiento interno
======================

Este capítulo te dirá un poco sobre cómo funciona *Silex* internamente.

*Silex*
-------

Aplicación
~~~~~~~~~~

La aplicación es la interfaz principal de *Silex*. Implementa la `HttpKernelInterface <http://api.symfony.com/2.0/Symfony/Component/HttpKernel/HttpKernelInterface.html>`_ de *Symfony2*, por lo tanto puedes pasar una `Petición <http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/Request.html>`_ al método ``handle`` y devolverá una `Respuesta <http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/Response.html>`_.

Esta extiende el contenedor de servicios *Pimple*, lo cual permite flexibilidad tanto en el exterior cómo en el interior. puedes sustituir cualquier servicio, y también serás capaz de leerlo.

La aplicación usa exhaustivamente el `EventDispatcher <http://api.symfony.com/master/Symfony/Component/EventDispatcher/EventDispatcher.html>`_ para engancharse a los eventos `HttpKernel
<http://api.symfony.com/master/Symfony/Component/HttpKernel/HttpKernel.html>`_ de *Symfony2*. Esto te permite recuperar la ``Petición``, convertir las cadenas de respuesta en objetos ``Respuesta`` y manipular excepciones. También lo usamos para despachar algunos eventos personalizados como los filtros ``before/after`` y errores.

Controlador
~~~~~~~~~~~

El `Enrutador
<http://api.symfony.com/master/Symfony/Component/Routing/Route.html>`_ de *Symfony2* en realidad es bastante potente. Puedes nombrar rutas, lo cual te permite generar *URL*. También puedes tener requisitos para las partes variables. A fin de permitirte una interfaz agradable a través de estos ajustes en el método ``match`` (que es utilizado por ``get``, ``post``, etc.) devolviendo una instancia del ``Controller``, que envuelve una ruta.

``ControllerCollection``
~~~~~~~~~~~~~~~~~~~~~~~~

Uno de los objetivos de exponer el `RouteCollection <http://api.symfony.com/master/Symfony/Component/Routing/RouteCollection.html>`_ era hacerlo mutable, para que los proveedores le puedan agregar cosas.
Aquí, el reto es el hecho de que las rutas no saben nada acerca de su nombre. El nombre sólo tiene sentido en el contexto de la ``RouteCollection`` y no se puede cambiar.

Para resolver este desafío, se nos ocurrió tener una área de estacionamiento para las rutas. La ``ControllerCollection`` tiene los controladores hasta que se llama a ``flush``, momento en el cual las rutas se añaden a la ``RouteCollection``. Además, los controladores se congelan. Esto significa que ya no se pueden modificar y se produce una excepción si se intenta hacerlo.

Desafortunadamente no se ha podido encontrar una buena manera para lavar implícitamente, por lo que el lavado ahora siempre es explícito. La aplicación se debe vaciar, pero si quieres leer la ``ControllerCollection`` antes de que se lleve a cabo la petición, tendrás que llamar a ``flush`` tú mismo.

La ``Appication`` proporciona un atajo al método ``flush`` para el lavado del ``ControllerCollection``.

*Symfony2*
----------

*Silex* utiliza los siguientes componentes de *Symfony2*:

* **ClassLoader**: Para cargar clases automáticamente.

* **HttpFoundation**: Para ``Petición`` y ``Respuesta``.

* **HttpKernel**: Porque necesitamos un corazón.

* **Routing**: Para concordancia de las rutas definidas.

* **EventDispatcher**: Para conectar al ``HttpKernel``.

Para más información, visita el sitio web de `Symfony
<http://symfony.com/>`_.

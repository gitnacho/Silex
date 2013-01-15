Cómo convertir errores a excepciones
====================================

*Silex* capturará excepciones lanzadas dentro de un ciclo petición/respuesta. Sin embargo, *no* captura errores y avisos de *PHP*. Los puedes capturar convirtiéndolos en excepciones, esta receta te dirá cómo.

¿Por qué *Silex* no hace esto?
------------------------------

En teoría, *Silex* lo podría hacer automáticamente, pero hay una razón por la cual no lo hace. *Silex* actúa como biblioteca, esto significa que no desordena ningún estado global. Debido  que los controladores de error son globales en *PHP*, como usuario es tu responsabilidad registrarlos.

Registrando el ``ErrorHandler``
-------------------------------

Afortunadamente, *Silex* viene con un ``ErrorHandler`` (es parte del paquete ``HttpKernel``) el cual soluciona este problema. Este convierte todos los errores en excepciones, y *Silex* puede capturar excepciones.

Tú las registras llamando el método estático ``register``::

    use Symfony\Component\HttpKernel\Debug\ErrorHandler;

    ErrorHandler::register();

Es recomendable que lo hagas en tu controlador frontal, es decir en :file:`web/index.php`.

.. note::

    El ``ErrorHandler`` nada tiene que hacer con el ``ExceptionHandler``. Es responsabilidad del ``ExceptionHandler`` mostrar elegantemente las excepciones que capture.

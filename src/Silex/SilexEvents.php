
.. code-block:: php

    <?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Para información completa sobre los derechos de autor y licencia, por
 * favor, ve el archivo LICENSE que viene con este código fuente.
 */

namespace Silex;

/**
 * The Silex events.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
final class SilexEvents
{
    const BEFORE = 'silex.before';
    const AFTER  = 'silex.after';
    const ERROR  = 'silex.error';
}

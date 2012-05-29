
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

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * GetResponseForExceptionEvent con un método setStringResponse adicional
 *
 * setStringResponse convertirá cadenas a objetos respuesta.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class GetResponseForErrorEvent extends GetResponseForExceptionEvent
{
    public function setStringResponse($response)
    {
        $converter = new StringResponseConverter();
        $this->setResponse($converter->convert($response));
    }
}

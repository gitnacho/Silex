
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

use Symfony\Component\HttpFoundation\Response;

/**
 * Converts string responses to Response objects.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class StringResponseConverter
{
    /**
     * Does the conversion
     *
     * @param string $response The response string
     *
     * @return A Response object
     */
    public function convert($response)
    {
        if (!$response instanceof Response) {
            return new Response((string) $response);
        }

        return $response;
    }
}

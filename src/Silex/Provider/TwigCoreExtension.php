
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

namespace Silex\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Twig extension.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TwigCoreExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'render' => new \Twig_Function_Method($this, 'render', array('needs_environment' => true, 'is_safe' => array('html'))),
        );
    }

    public function render(\Twig_Environment $twig, $uri)
    {
        $globals = $twig->getGlobals();
        $request = $globals['app']['request'];

        $subRequest = Request::create($uri, 'get', array(), $request->cookies->all(), array(), $request->server->all());
        if ($request->getSession()) {
            $subRequest->setSession($request->getSession());
        }

        $response = $globals['app']->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(sprintf('Error when rendering "%s" (Status code is %s).', $request->getUri(), $response->getStatusCode()));
        }

        return $response->getContent();
    }

    public function getName()
    {
        return 'silex';
    }
}

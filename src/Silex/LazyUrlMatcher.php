
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

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Implements a lazy UrlMatcher.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class LazyUrlMatcher implements UrlMatcherInterface
{
    private $factory;
    private $urlMatcher;

    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Returns the corresponding UrlMatcherInterface instance.
     *
     * @return UrlMatcherInterface
     */
    public function getUrlMatcher()
    {
        $urlMatcher = call_user_func($this->factory);
        if (!$urlMatcher instanceof UrlMatcherInterface) {
            throw new \LogicException("Factory supplied to LazyUrlMatcher must return implementation of UrlMatcherInterface.");
        }
        return $urlMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        return $this->getUrlMatcher()->match($pathinfo);
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->getUrlMatcher()->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->getUrlMatcher()->getContext();
    }
}

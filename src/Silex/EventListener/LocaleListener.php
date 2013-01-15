<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Para información completa sobre los derechos de autor y licencia, por
 * favor, ve el archivo LICENSE que viene con este código fuente.
 */

namespace Silex\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Silex\Application;

/**
 * Initializes the locale based on the current request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LocaleListener extends BaseLocaleListener
{
    protected $app;

    public function __construct(Application $app, RequestContextAwareInterface $router = null)
    {
        parent::__construct($app['locale'], $router);

        $this->app = $app;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        parent::onKernelRequest($event);

        $this->app['locale'] = $event->getRequest()->getLocale();
    }
}

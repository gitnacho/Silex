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

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Manipula convertidores.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConverterListener implements EventSubscriberInterface
{
    protected $routes;

    /**
     * Constructor.
     *
     * @param RouteCollection $routes A RouteCollection instance
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Manipula convertidores.
     *
     * @param FilterControllerEvent $event The event to handle
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $route = $this->routes->get($request->attributes->get('_route'));
        if ($route && $converters = $route->getOption('_converters')) {
            foreach ($converters as $name => $callback) {
                $request->attributes->set($name, call_user_func($callback, $request->attributes->get($name), $request));
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
        );
    }
}

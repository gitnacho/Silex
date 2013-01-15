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
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts string responses to proper Response instances.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class StringToResponseListener implements EventSubscriberInterface
{
    /**
     * Manipula respuestas de cadena.
     *
     * @param GetResponseForControllerResultEvent $event The event to handle
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $response = $event->getControllerResult();

        if (!(
            null === $response
            || is_array($response)
            || $response instanceof Response
            || (is_object($response) && !method_exists($response, '__toString'))
        )) {
            $event->setResponse(new Response((string) $response));
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::VIEW => array('onKernelView', -10),
        );
    }
}

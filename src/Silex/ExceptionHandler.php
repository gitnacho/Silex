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

use Symfony\Component\HttpKernel\Debug\ExceptionHandler as DebugExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Manipulador de excepción predefinida.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExceptionHandler implements EventSubscriberInterface
{
    protected $debug;
    protected $enabled;

    public function __construct($debug)
    {
        $this->debug = $debug;
        $this->enabled = true;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function onSilexError(GetResponseForExceptionEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $handler = new DebugExceptionHandler($this->debug);

        $event->setResponse($handler->createResponse($event->getException()));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(KernelEvents::EXCEPTION => array('onSilexError', -255));
    }
}

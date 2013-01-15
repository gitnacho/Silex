<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Para información completa sobre los derechos de autor y licencia, por
 * favor, ve el archivo LICENSE que viene con este código fuente.
 */

namespace Silex\Application;

use Monolog\Logger;

/**
 * Monolog trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait MonologTrait
{
    /**
     * Adds a log record.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @param integer $level   The logging level
     *
     * @return Boolean Whether the record has been processed
     */
    public function log($message, array $context = array(), $level = Logger::INFO)
    {
        return $this['monolog']->addRecord($level, $message, $context);
    }
}

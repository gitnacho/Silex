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

/**
 * Swiftmailer trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait SwiftmailerTrait
{
    /**
     * Envía correo electrónico.
     *
     * @param \Swift_Message $message A \Swift_Message instance
     */
    public function mail(\Swift_Message $message)
    {
        return $this['mailer']->send($message);
    }
}

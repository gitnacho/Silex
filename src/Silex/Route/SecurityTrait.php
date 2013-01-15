<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Para información completa sobre los derechos de autor y licencia, por
 * favor, ve el archivo LICENSE que viene con este código fuente.
 */

namespace Silex\Route;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Security trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait SecurityTrait
{
    public function secure($roles)
    {
        $this->before(function ($request, $app) use ($roles) {
            if (!$app['security']->isGranted($roles)) {
                throw new AccessDeniedException();
            }
        });
    }
}

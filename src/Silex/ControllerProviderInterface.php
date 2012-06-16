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

/**
 * Interfaz para proveedores de controlador.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface ControllerProviderInterface
{
    /**
     * Devuelve rutas para conectar a la aplicación dada.
     *
     * @param Application $app Una instancia de Application
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app);
}

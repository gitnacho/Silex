<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Para información completa sobre los derechos de autor y licencia, por
 * favor, ve el archivo LICENSE que viene con este código fuente.
 */

namespace Silex\Exception;

/**
 * La excepción, es lanzada cuando modificas un controlador congelado
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ControllerFrozenException extends \RuntimeException
{
}

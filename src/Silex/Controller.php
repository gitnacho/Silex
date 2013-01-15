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

use Silex\Exception\ControllerFrozenException;

/**
 * Una envoltura para un controlador, asignado a una ruta.
 *
 * __call() forwards method-calls to Route, but returns instance of Controller
 * listing Route's methods below, so that IDEs know they are valid
 *
 * @method \Silex\Controller assert(string $variable, string $regexp)
 * @method \Silex\Controller value(string $variable, mixed $default)
 * @method \Silex\Controller convert(string $variable, mixed $callback)
 * @method \Silex\Controller method(string $method)
 * @method \Silex\Controller requireHttp()
 * @method \Silex\Controller requireHttps()
 * @method \Silex\Controller before(mixed $callback)
 * @method \Silex\Controller after(mixed $callback)
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class Controller
{
    private $route;
    private $routeName;
    private $isFrozen = false;

    /**
     * Constructor.
     *
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * Obtiene la ruta del controlador.
     *
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Obtiene el nombre de la ruta del controlador.
     *
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * Establece la ruta del controlador.
     *
     * @param string $routeName
     *
     * @return Controller $this La instancia de Controller actual
     */
    public function bind($routeName)
    {
        if ($this->isFrozen) {
            throw new ControllerFrozenException(sprintf('Llamando a %s en una instancia congelada de %s.', __METHOD__, __CLASS__));
        }

        $this->routeName = $routeName;

        return $this;
    }

    public function __call($method, $arguments)
    {
        if (!method_exists($this->route, $method)) {
            throw new \BadMethodCallException(sprintf('Method "%s::%s" does not exist.', get_class($this->route), $method));
        }

        call_user_func_array(array($this->route, $method), $arguments);

        return $this;
    }

    /**
     * Congela el controlador.
     *
     * Una vez congelado el controlador, no puedes cambiar el nombre de la ruta
     */
    public function freeze()
    {
        $this->isFrozen = true;
    }

    public function generateRouteName($prefix)
    {
        $requirements = $this->route->getRequirements();
        $method = isset($requirements['_method']) ? $requirements['_method'] : '';

        $routeName = $prefix.$method.$this->route->getPattern();
        $routeName = str_replace(array('/', ':', '|', '-'), '_', $routeName);
        $routeName = preg_replace('/[^a-z0-9A-Z_.]+/', '', $routeName);

        return $routeName;
    }
}

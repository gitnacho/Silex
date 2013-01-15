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

use Symfony\Component\Routing\Route as BaseRoute;

/**
 * Una envoltura para un controlador, asignado a una ruta.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Route extends BaseRoute
{
    public function __construct($pattern = '', array $defaults = array(), array $requirements = array(), array $options = array())
    {
        parent::__construct($pattern, $defaults, $requirements, $options);
    }

    /**
     * Establece los requisitos para una ruta variable.
     *
     * @param string $variable El nombre variable
     * @param string $regexp   La expresión regular por aplicar
     *
     * @return Route $this The current route instance
     */
    public function assert($variable, $regexp)
    {
        $this->setRequirement($variable, $regexp);

        return $this;
    }

    /**
     * Establece el valor predefinido para una variable de ruta.
     *
     * @param string $variable El nombre variable
     * @param mixed  $default  El valor predeterminado
     *
     * @return Route $this The current Route instance
     */
    public function value($variable, $default)
    {
        $this->setDefault($variable, $default);

        return $this;
    }

    /**
     * Establece un convertidor para una variable de ruta.
     *
     * @param string $variable El nombre variable
     * @param mixed  $callback Una retrollamada PHP que convierte el valor original
     *
     * @return Route $this The current Route instance
     */
    public function convert($variable, $callback)
    {
        $converters = $this->getOption('_converters');
        $converters[$variable] = $callback;
        $this->setOption('_converters', $converters);

        return $this;
    }

    /**
     * Establece los requisitos para el método HTTP.
     *
     * @param string $method El nombre del método HTTP. Puedes suplir múltiples métodos, delimitados por un carácter de tubería '|', p.e. 'GET|POST'
     *
     * @return Route $this The current Route instance
     */
    public function method($method)
    {
        $this->setRequirement('_method', $method);

        return $this;
    }

    /**
     * Sets the requirement of HTTP (no HTTPS) on this Route.
     *
     * @return Route $this The current Route instance
     */
    public function requireHttp()
    {
        $this->setRequirement('_scheme', 'http');

        return $this;
    }

    /**
     * Sets the requirement of HTTPS on this Route.
     *
     * @return Route $this The current Route instance
     */
    public function requireHttps()
    {
        $this->setRequirement('_scheme', 'https');

        return $this;
    }

    /**
     * Establece una retrollamada para manipular retrollamadas de ruta before.
     *
     * @param mixed  $callback Una retrollamada PHP a lanzar cuando la ruta coincide,
     *                         justo antes de la retrollamada de la ruta
     * @return Route $this The current Route instance
     */
    public function before($callback)
    {
        $callbacks = $this->getOption('_before_middlewares');
        $callbacks[] = $callback;
        $this->setOption('_before_middlewares', $callbacks);

        return $this;
    }

    /**
     * Define una retrollamada a manipular después de la retrollamada a la ruta.
     *
     * @param mixed $callback Una retrollamada PHP a lanzar después de la retrollamada a la ruta
     *
     * @return Route $this The current Route instance
     */
    public function after($callback)
    {
        $callbacks = $this->getOption('_after_middlewares');
        $callbacks[] = $callback;
        $this->setOption('_after_middlewares', $callbacks);

        return $this;
    }
}

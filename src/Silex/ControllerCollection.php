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

use Symfony\Component\Routing\RouteCollection;
use Silex\Controller;

/**
 * Construye los controladores de Silex.
 *
 * Actúa como una área de preparación para las rutas. Podrás configurar el nombre de la ruta
 * hasta invocar a flush(), en cuyo punto todos los controladores serán
 * congelados y convertidos a una RouteCollection.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerCollection
{
    protected $controllers = array();
    protected $defaultRoute;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->defaultRoute = new Route('');
    }

    /**
     * Asigna un patrón a un ejecutable.
     *
     * Opcionalmente puedes especificar los métodos HTTP con que coincidirá.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function match($pattern, $to)
    {
        $route = clone $this->defaultRoute;
        $route->setPattern($pattern);
        $route->setDefault('_controller', $to);

        $this->controllers[] = $controller = new Controller($route);

        return $controller;
    }

    /**
     * Asigna una petición GET a un ejecutable.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function get($pattern, $to)
    {
        return $this->match($pattern, $to)->method('GET');
    }

    /**
     * Asigna una petición POST a un ejecutable.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function post($pattern, $to)
    {
        return $this->match($pattern, $to)->method('POST');
    }

    /**
     * Asigna una petición PUT a un ejecutable.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function put($pattern, $to)
    {
        return $this->match($pattern, $to)->method('PUT');
    }

    /**
     * Asigna una petición DELETE a un ejecutable.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function delete($pattern, $to)
    {
        return $this->match($pattern, $to)->method('DELETE');
    }

    /**
     * Establece los requisitos para una ruta variable.
     *
     * @param string $variable El nombre variable
     * @param string $regexp   The regexp to apply
     *
     * @return ControllerCollection $this The current Controller instance
     */
    public function assert($variable, $regexp)
    {
        $this->defaultRoute->assert($variable, $regexp);

        foreach ($this->controllers as $controller) {
            $controller->assert($variable, $regexp);
        }

        return $this;
    }

    /**
     * Establece el valor predefinido para una variable de ruta.
     *
     * @param string $variable El nombre variable
     * @param mixed  $default  The default value
     *
     * @return ControllerCollection $this The current Controller instance
     */
    public function value($variable, $default)
    {
        $this->defaultRoute->value($variable, $default);

        foreach ($this->controllers as $controller) {
            $controller->value($variable, $default);
        }

        return $this;
    }

    /**
     * Establece un convertidor para una variable de ruta.
     *
     * @param string $variable El nombre variable
     * @param mixed  $callback A PHP callback that converts the original value
     *
     * @return ControllerCollection $this The current Controller instance
     */
    public function convert($variable, $callback)
    {
        $this->defaultRoute->convert($variable, $callback);

        foreach ($this->controllers as $controller) {
            $controller->convert($variable, $callback);
        }

        return $this;
    }

    /**
     * Establece los requisitos para el método HTTP.
     *
     * @param string $method El nombre del método HTTP. Puedes suplir múltiples métodos, delimitados por un carácter de tubería '|', p.e. 'GET|POST'
     *
     * @return ControllerCollection $this The current Controller instance
     */
    public function method($method)
    {
        $this->defaultRoute->method($method);

        foreach ($this->controllers as $controller) {
            $controller->method($method);
        }

        return $this;
    }

    /**
     * Establece los requisitos de HTTP (no HTTPS) en este controlador.
     *
     * @return ControllerCollection $this The current Controller instance
     */
    public function requireHttp()
    {
        $this->defaultRoute->requireHttp();

        foreach ($this->controllers as $controller) {
            $controller->requireHttp();
        }

        return $this;
    }

    /**
     * Establece los requisitos HTTPS en este controlador.
     *
     * @return ControllerCollection $this The current Controller instance
     */
    public function requireHttps()
    {
        $this->defaultRoute->requireHttps();

        foreach ($this->controllers as $controller) {
            $controller->requireHttps();
        }

        return $this;
    }

    /**
     * Establece una retrollamada para manipular retrollamadas de ruta before.
     *
     * @param mixed $callback A PHP callback to be triggered when the Route is matched, just before the route callback
     *
     * @return ControllerCollection $this The current ControllerCollection instance
     */
    public function before($callback)
    {
        $this->defaultRoute->before($callback);

        foreach ($this->controllers as $controller) {
            $controller->before($callback);
        }

        return $this;
    }

    /**
     * Sets a callback to handle after the route callback.
     *
     * @param mixed $callback A PHP callback to be triggered after the route callback
     *
     * @return ControllerCollection $this The current ControllerCollection instance
     */
    public function after($callback)
    {
        $this->defaultRoute->after($callback);

        foreach ($this->controllers as $controller) {
            $controller->after($callback);
        }

        return $this;
    }

    /**
     * Persiste y congela controladores congelados.
     *
     * @param string $prefix
     *
     * @return RouteCollection Una instancia de RouteCollection
     */
    public function flush($prefix = '')
    {
        $routes = new RouteCollection();

        foreach ($this->controllers as $controller) {
            if (!$name = $controller->getRouteName()) {
                $name = $controller->generateRouteName($prefix);
                while ($routes->get($name)) {
                    $name .= '_';
                }
                $controller->bind($name);
            }
            $routes->add($name, $controller->getRoute());
            $controller->freeze();
        }

        $this->controllers = array();

        return $routes;
    }
}

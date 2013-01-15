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
    public function __construct(Route $defaultRoute)
    {
        $this->defaultRoute = $defaultRoute;
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

    public function __call($method, $arguments)
    {
        if (!method_exists($this->defaultRoute, $method)) {
            throw new \BadMethodCallException(sprintf('Method "%s::%s" does not exist.', get_class($this->defaultRoute), $method));
        }

        call_user_func_array(array($this->defaultRoute, $method), $arguments);

        foreach ($this->controllers as $controller) {
            call_user_func_array(array($controller, $method), $arguments);
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

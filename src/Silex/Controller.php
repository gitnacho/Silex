
.. code-block:: php

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

use Symfony\Component\Routing\Route;

/**
 * Una envoltura para un controlador, asignado a una ruta.
 *
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

    /**
     * Establece los requisitos para una ruta variable.
     *
     * @param string $variable El nombre variable
     * @param string $regexp   La expresión regular por aplicar
     * @return Controller $this La instancia del controlador actual
     */
    public function assert($variable, $regexp)
    {
        $this->route->setRequirement($variable, $regexp);

        return $this;
    }

    /**
     * Establece el valor predefinido para una variable de ruta.
     *
     * @param string $variable El nombre variable
     * @param mixed  $default  El valor predefinido
     * @return Controller $this La instancia del controlador actual
     */
    public function value($variable, $default)
    {
        $this->route->setDefault($variable, $default);

        return $this;
    }

    /**
     * Establece un convertidor para una variable de ruta.
     *
     * @param string $variable El nombre variable
     * @param mixed  $callback Una retrollamada PHP que convierta al valor original
     * @return Controller $this La instancia del controlador actual
     */
    public function convert($variable, $callback)
    {
        $converters = $this->route->getOption('_converters');
        $converters[$variable] = $callback;
        $this->route->setOption('_converters', $converters);

        return $this;
    }

    /**
     * Establece los requisitos para el método HTTP.
     *
     * @param string $method El nombre del método HTTP. Puedes suplir múltiples métodos, delimitados por un carácter de tubería '|', p.e. 'GET|POST'.
     * @return Controller $this La instancia del controlador actual
     */
    public function method($method)
    {
        $this->route->setRequirement('_method', $method);

        return $this;
    }

    /**
     * Establece los requisitos de HTTP (no HTTPS) en este controlador.
     *
     * @return Controller $this La instancia del controlador actual
     */
    public function requireHttp()
    {
        $this->route->setRequirement('_scheme', 'http');

        return $this;
    }

    /**
     * Establece los requisitos HTTPS en este controlador.
     *
     * @return Controller $this La instancia del controlador actual
     */
    public function requireHttps()
    {
        $this->route->setRequirement('_scheme', 'https');

        return $this;
    }

    /**
     * Establece una retrollamada para manipular retrollamadas de ruta before.
     * (alias "Lógica intermedia de ruta")
     *
     * @param mixed  $callback Una retrollamada PHP a lanzar cuando la ruta coincide, justo antes de la retrollamada de la ruta
     * @return Controller $this La instancia del controlador actual
     */
    public function middleware($callback)
    {
        $middlewareCallbacks = $this->route->getDefault('_middlewares');
        $middlewareCallbacks[] = $callback;
        $this->route->setDefault('_middlewares', $middlewareCallbacks);

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

    public function bindDefaultRouteName($prefix)
    {
        $requirements = $this->route->getRequirements();
        $method = isset($requirements['_method']) ? $requirements['_method'] : '';

        $routeName = $prefix.$method.$this->route->getPattern();
        $routeName = str_replace(array('/', ':', '|', '-'), '_', $routeName);
        $routeName = preg_replace('/[^a-z0-9A-Z_.]+/', '', $routeName);

        $this->routeName = $routeName;
    }
}

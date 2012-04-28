
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

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
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

    /**
     * Asigna un patrón a un ejecutable.
     *
     * Opcionalmente puedes especificar los métodos HTTP con que coincidirá.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed $to Retrollamar que devuelve la respuesta cuando coincide
     *
     * @return Silex\Controller
     */
    public function match($pattern, $to)
    {
        $route = new Route($pattern, array('_controller' => $to));
        $controller = new Controller($route);
        $this->add($controller);

        return $controller;
    }

    /**
     * Asigna una petición GET a un ejecutable.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed $to Retrollamar que devuelve la respuesta cuando coincide
     *
     * @return Silex\Controller
     */
    public function get($pattern, $to)
    {
        return $this->match($pattern, $to)->method('GET');
    }

    /**
     * Asigna una petición POST a un ejecutable.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed $to Retrollamar que devuelve la respuesta cuando coincide
     *
     * @return Silex\Controller
     */
    public function post($pattern, $to)
    {
        return $this->match($pattern, $to)->method('POST');
    }

    /**
     * Asigna una petición PUT a un ejecutable.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed $to Retrollamar que devuelve la respuesta cuando coincide
     *
     * @return Silex\Controller
     */
    public function put($pattern, $to)
    {
        return $this->match($pattern, $to)->method('PUT');
    }

    /**
     * Asigna una petición DELETE a un ejecutable.
     *
     * @param string $pattern Patrón de ruta coincidente
     * @param mixed $to Retrollamar que devuelve la respuesta cuando coincide
     *
     * @return Silex\Controller
     */
    public function delete($pattern, $to)
    {
        return $this->match($pattern, $to)->method('DELETE');
    }

    /**
     * Añade un controlador al área de preparación.
     *
     * @param Controller $controller
     */
    public function add(Controller $controller)
    {
        $this->controllers[] = $controller;
    }

    /**
     * Persiste y congela controladores congelados.
     *
     * @return RouteCollection Una instancia de RouteCollection
     */
    public function flush($prefix = '')
    {
        $routes = new RouteCollection();

        foreach ($this->controllers as $controller) {
            if (!$controller->getRouteName()) {
                $controller->bindDefaultRouteName($prefix);
            }
            $routes->add($controller->getRouteName(), $controller->getRoute());
            $controller->freeze();
        }

        $this->controllers = array();

        return $routes;
    }
}

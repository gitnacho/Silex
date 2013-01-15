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

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Silex\RedirectableUrlMatcher;
use Silex\ControllerResolver;
use Silex\EventListener\LocaleListener;
use Silex\EventListener\MiddlewareListener;
use Silex\EventListener\ConverterListener;
use Silex\EventListener\StringToResponseListener;

/**
 * La clase de la plataforma Silex.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Application extends \Pimple implements HttpKernelInterface, TerminableInterface
{
    const VERSION = '1.0-DEV';

    const EARLY_EVENT = 512;
    const LATE_EVENT  = -512;

    private $providers = array();
    private $booted = false;

    /**
     * Instantiate a new Application.
     *
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @param array $values The parameters or objects.
     */
    public function __construct(array $values = array())
    {
        parent::__construct();

        $app = $this;

        $this['logger'] = null;

        $this['autoloader'] = function () {
            throw new \RuntimeException('Intentaste acceder al servicio del cargador automático. Se ha eliminado de Silex el cargador automático. Se recomienda utilizar Composer para gestionar tus dependencias y manejar tu carga automática. Ve http://getcomposer.org para más información.');
        };

        $this['routes'] = $this->share(function () {
            return new RouteCollection();
        });

        $this['controllers'] = $this->share(function () use ($app) {
            return $app['controllers_factory'];
        });

        $this['controllers_factory'] = function () use ($app) {
            return new ControllerCollection($app['route_factory']);
        };

        $this['route_class'] = 'Silex\\Route';
        $this['route_factory'] = function () use ($app) {
            return new $app['route_class']();
        };

        $this['exception_handler'] = $this->share(function () use ($app) {
            return new ExceptionHandler($app['debug']);
        });

        $this['dispatcher_class'] = 'Symfony\\Component\\EventDispatcher\\EventDispatcher';
        $this['dispatcher'] = $this->share(function () use ($app) {
            $dispatcher = new $app['dispatcher_class']();

            $urlMatcher = new LazyUrlMatcher(function () use ($app) {
                return $app['url_matcher'];
            });
            $dispatcher->addSubscriber(new RouterListener($urlMatcher, $app['request_context'], $app['logger']));
            $dispatcher->addSubscriber(new LocaleListener($app, $urlMatcher));
            if (isset($app['exception_handler'])) {
                $dispatcher->addSubscriber($app['exception_handler']);
            }
            $dispatcher->addSubscriber(new ResponseListener($app['charset']));
            $dispatcher->addSubscriber(new MiddlewareListener($app));
            $dispatcher->addSubscriber(new ConverterListener($app['routes']));
            $dispatcher->addSubscriber(new StringToResponseListener());

            return $dispatcher;
        });

        $this['resolver'] = $this->share(function () use ($app) {
            return new ControllerResolver($app, $app['logger']);
        });

        $this['kernel'] = $this->share(function () use ($app) {
            return new HttpKernel($app['dispatcher'], $app['resolver']);
        });

        $this['request_context'] = $this->share(function () use ($app) {
            $context = new RequestContext();

            $context->setHttpPort($app['request.http_port']);
            $context->setHttpsPort($app['request.https_port']);

            return $context;
        });

        $this['url_matcher'] = $this->share(function () use ($app) {
            return new RedirectableUrlMatcher($app['routes'], $app['request_context']);
        });

        $this['request_error'] = $this->protect(function () {
            throw new \RuntimeException('Servicio de respuesta accedido fuera del ámbito de la petición. Intenta moviendo esta llamada a un controlador "before".');
        });

        $this['request'] = $this['request_error'];

        $this['request.http_port'] = 80;
        $this['request.https_port'] = 443;
        $this['debug'] = false;
        $this['charset'] = 'UTF-8';
        $this['locale'] = 'en';

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * Registra un proveedor de servicio.
     *
     * @param ServiceProviderInterface $provider Una instancia de ServiceProviderInterface
     * @param array                    $values   Un arreglo de valores que personalizan el proveedor
     */
    public function register(ServiceProviderInterface $provider, array $values = array())
    {
        $this->providers[] = $provider;

        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * Boots all service providers.
     *
     * This method is automatically called by handle(), but you can use it
     * to boot all service providers when not handling a request.
     */
    public function boot()
    {
        if (!$this->booted) {
            foreach ($this->providers as $provider) {
                $provider->boot($this);
            }

            $this->booted = true;
        }
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
        return $this['controllers']->match($pattern, $to);
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
        return $this['controllers']->get($pattern, $to);
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
        return $this['controllers']->post($pattern, $to);
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
        return $this['controllers']->put($pattern, $to);
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
        return $this['controllers']->delete($pattern, $to);
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string   $eventName The event to listen on
     * @param callable $listener  The listener
     * @param integer  $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function on($eventName, $callback, $priority = 0)
    {
        $this['dispatcher']->addListener($eventName, $callback, $priority);
    }

    /**
     * Registra un filtro before.
     *
     * Los filtros before se ejecutan antes de buscar cualquier ruta coincidente.
     *
     * @param mixed   $callback Retrollamada a filtro before
     * @param integer $priority Mientras más alto este valor, más pronto se
     *                          lanzará el escucha del evento en la cadena (por omisión es 0)
     */
    public function before($callback, $priority = 0)
    {
        $this['dispatcher']->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) use ($callback) {
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
                return;
            }

            $ret = call_user_func($callback, $event->getRequest());

            if ($ret instanceof Response) {
                $event->setResponse($ret);
            }
        }, $priority);
    }

    /**
     * Registra un filtro after.
     *
     * Los filtros after se ejecutan después de ejecutado el controlador.
     *
     * @param mixed   $callback Retrollamada del filtro after
     * @param integer $priority Mientras más alto este valor, más pronto se
     *                          lanzará el escucha en la cadena (por omisión es 0)
     */
    public function after($callback, $priority = 0)
    {
        $this['dispatcher']->addListener(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($callback) {
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
                return;
            }

            call_user_func($callback, $event->getRequest(), $event->getResponse());
        }, $priority);
    }

    /**
     * Registra un filtro finish.
     *
     * Los filtros finish se puede ejecutar después de enviada la respuesta.
     *
     * @param mixed   $callback Retrollamada del filtro finish
     * @param integer $priority Mientras más alto este valor, más pronto se
     *                          listener will be triggered in the chain (defaults to 0)
     */
    public function finish($callback, $priority = 0)
    {
        $this['dispatcher']->addListener(KernelEvents::TERMINATE, function (PostResponseEvent $event) use ($callback) {
            call_user_func($callback, $event->getRequest(), $event->getResponse());
        }, $priority);
    }

    /**
     * Aborta la petición actual enviando un error HTTP adecuado.
     *
     * @param integer $statusCode El código de estado HTTP
     * @param string  $message    El mensaje de estado
     * @param array   $headers    Un arreglo de cabeceras HTTP
     */
    public function abort($statusCode, $message = '', array $headers = array())
    {
        throw new HttpException($statusCode, $message, null, $headers);
    }

    /**
     * Registra un manipulador de error.
     *
     * Los manipuladores de error son retrollamadas sencillas que toman
     * una sola excepción como argumento. Si un controlador lanza una excepción, un manipulador
     * de error puede devolver una respuesta específica.
     *
     * Cuando ocurre una excepción, todos los manipuladores serán llamados, hasta
     * que uno regrese algo (una cadena o un objeto respuesta), punto en el cual
     * será devuelto al cliente.
     *
     * Por esta razón debes añadir manipuladores de registro antes de los controladores.
     *
     * @param mixed   $callback Retrollamada controlador de error, toma una Excepción como argumento
     * @param integer $priority Mientras más alto este valor, más pronto se
     *                          listener will be triggered in the chain (defaults to -8)
     */
    public function error($callback, $priority = -8)
    {
        $this['dispatcher']->addListener(KernelEvents::EXCEPTION, new ExceptionListenerWrapper($this, $callback), $priority);
    }

    /**
     * Vacía la colección de controladores.
     *
     * @param string $prefix El prefijo de la ruta
     */
    public function flush($prefix = '')
    {
        $this['routes']->addCollection($this['controllers']->flush($prefix), $prefix);
    }

    /**
     * Redirige al usuario a otra URL.
     *
     * @param string  $url    La URL a la cual redirigir
     * @param integer $status El código de estado (el predefinido es 302)
     *
     * @see RedirectResponse
     */
    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Crea una respuesta a transmitir.
     *
     * @param mixed   $callback Una retrollamada PHP válida
     * @param integer $status   El código de estado de la respuesta
     * @param array   $headers  Un arreglo de cabeceras de la respuesta
     *
     * @see StreamedResponse
     */
    public function stream($callback = null, $status = 200, $headers = array())
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Escapa un texto para HTML.
     *
     * @param string  $text         El campo de entrada de texto a escapar
     * @param integer $flags        Los indicadores (@see htmlspecialchars)
     * @param string  $charset      El juego de caracteres
     * @param Boolean $doubleEncode Whether to try to avoid double escaping or not
     *
     * @return string Escaped texto
     */
    public function escape($text, $flags = ENT_COMPAT, $charset = null, $doubleEncode = true)
    {
        return htmlspecialchars($text, $flags, $charset ?: $this['charset'], $doubleEncode);
    }

    /**
     * Convierte algunos datos en una respuesta JSON.
     *
     * @param mixed   $data    Los datos de la respuesta
     * @param integer $status  El código de estado de la respuesta
     * @param array   $headers Un arreglo de cabeceras de la respuesta
     *
     * @see JsonResponse
     */
    public function json($data = array(), $status = 200, $headers = array())
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Mounts controllers under the given route prefix.
     *
     * @param string                                           $prefix      The route prefix
     * @param ControllerCollection|ControllerProviderInterface $controllers A ControllerCollection or a ControllerProviderInterface instance
     */
    public function mount($prefix, $controllers)
    {
        if ($controllers instanceof ControllerProviderInterface) {
            $controllers = $controllers->connect($this);
        }

        if (!$controllers instanceof ControllerCollection) {
            throw new \LogicException('El método "mount" toma bien una instancia de ControllerCollection o ControllerProviderInterface.');
        }

        $this['routes']->addCollection($controllers->flush($prefix), $prefix);
    }

    /**
     * Handles the request and delivers the response.
     *
     * @param Request $request Petición a procesar
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $response = $this->handle($request);
        $response->send();
        $this->terminate($request, $response);
    }

    /**
     * {@inheritdoc}
     *
     * If you call this method directly instead of run(), you must call the
     * terminate() method yourself if you want the finish filters to be run.
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (!$this->booted) {
            $this->boot();
        }

        $current = HttpKernelInterface::SUB_REQUEST === $type ? $this['request'] : $this['request_error'];

        $this['request'] = $request;

        $this->flush();

        $response = $this['kernel']->handle($request, $type, $catch);

        $this['request'] = $current;

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        $this['kernel']->terminate($request, $response);
    }
}


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

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Silex\RedirectableUrlMatcher;
use Silex\ControllerResolver;

/**
 * La clase de la plataforma Silex.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Application extends \Pimple implements HttpKernelInterface, EventSubscriberInterface
{
    const VERSION = '@package_version@';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $app = $this;

        $this['autoloader'] = $this->share(function () {
            $loader = new UniversalClassLoader();
            $loader->register();

            return $loader;
        });

        $this['routes'] = $this->share(function () {
            return new RouteCollection();
        });

        $this['controllers'] = $this->share(function () use ($app) {
            return new ControllerCollection();
        });

        $this['exception_handler'] = $this->share(function () {
            return new ExceptionHandler();
        });

        $this['dispatcher'] = $this->share(function () use ($app) {
            $despachador = new EventDispatcher();
            $dispatcher->addSubscriber($app);

            $urlMatcher = new LazyUrlMatcher(function () use ($app) {
                return $app['url_matcher'];
            });
            $dispatcher->addSubscriber(new RouterListener($urlMatcher));

            return $dispatcher;
        });

        $this['resolver'] = $this->share(function () use ($app) {
            return new ControllerResolver($app);
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

        $this['route_middlewares_trigger'] = $this->protect(function (KernelEvent $event) use ($app) {
            foreach ($event->getRequest()->attributes->get('_middlewares', array()) as $callback) {
                $ret = call_user_func($callback, $event->getRequest());
                if ($ret instanceof Response) {
                    $event->setResponse($ret);
                    return;
                } elseif (null !== $ret) {
                    throw new \RuntimeException('soporte lógico intermedio para ruta "'.$event->getRequest()->attributes->get('_route').'" devuelve un valor de respuesta inválido. Debe regresar null o una instancia de Respuesta.');
                }
            }
        });

        $this['request.default_locale'] = 'en';

        $this['request'] = function () {
            throw new \RuntimeException('Servicio de respuesta accedido fuera del ámbito de la petición. Intenta moviendo esta llamada a un controlador "before".');
        };

        $this['request.http_port'] = 80;
        $this['request.https_port'] = 443;
        $this['debug'] = false;
        $this['charset'] = 'UTF-8';
    }

    /**
     * Registra un proveedor de servicio.
     *
     * @param ServiceProviderInterface $provider Una instancia de ServiceProviderInterface
     * @param array                    $values   Un arreglo de valores que personalizan el proveedor
     */
    public function register(ServiceProviderInterface $provider, array $values = array())
    {
        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        $provider->register($this);
    }

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
        return $this['controllers']->match($pattern, $to);
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
        return $this['controllers']->get($pattern, $to);
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
        return $this['controllers']->post($pattern, $to);
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
        return $this['controllers']->put($pattern, $to);
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
        return $this['controllers']->delete($pattern, $to);
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
        $this['dispatcher']->addListener(SilexEvents::BEFORE, function (GetResponseEvent $event) use ($callback) {
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
        $this['dispatcher']->addListener(SilexEvents::AFTER, function (FilterResponseEvent $event) use ($callback) {
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
     *                          lanzará el escucha en la cadena (por omisión es 0)
     */
    public function error($callback, $priority = 0)
    {
        $this['dispatcher']->addListener(SilexEvents::ERROR, function (GetResponseForErrorEvent $event) use ($callback) {
            $exception = $event->getException();
            $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

            $result = call_user_func($callback, $exception, $code);

            if (null !== $result) {
                $event->setStringResponse($result);
            }
        }, $priority);
    }

    /**
     * Flushes the controller collection.
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
     * @see Symfony\Component\HttpFoundation\RedirectResponse
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
     * @param array   $headers  Una matriz de cabeceras de la respuesta
     *
     * @see Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream($callback = null, $status = 200, $headers = array())
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Escapa un texto para HTML.
     *
     * @param string  $text         El campo de entrada de texto a escapar
     * @param integer $flags        The flags (@see htmlspecialchars)
     * @param string  $charset      El juego de caracteres
     * @param Boolean $doubleEncode Whether to try to avoid double escaping or not
     *
     * @return string Escaped text
     */
    public function escape($text, $flags = ENT_COMPAT, $charset = null, $doubleEncode = true)
    {
        return htmlspecialchars($text, $flags, $charset ?: $this['charset'], $doubleEncode);
    }

    /**
     * Convert some data into a JSON response.
     *
     * @param mixed   $data    The response data
     * @param integer $status  The response status code
     * @param array   $headers An array of response headers
     *
     * @see Symfony\Component\HttpFoundation\JsonResponse
     */
    public function json($data = array(), $status = 200, $headers = array())
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Monta una aplicación bajo el prefijo de ruta dado.
     *
     * @param string $prefix El prefijo de la ruta
     * @param ControllerCollection|ControllerProviderInterface $app Una instancia de ControllerCollection o ControllerProviderInterface
     */
    public function mount($prefix, $app)
    {
        if ($app instanceof ControllerProviderInterface) {
            $app = $app->connect($this);
        }

        if (!$app instanceof ControllerCollection) {
            throw new \LogicException('El método "mount" toma bien una instancia de ControllerCollection o ControllerProviderInterface.');
        }

        $this['routes']->addCollection($app->flush($prefix), $prefix);
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

        $this->handle($request)->send();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $this->beforeDispatched = false;

        $this['request'] = $request;
        $this['request']->setDefaultLocale($this['request.default_locale']);

        $this->flush();

        return $this['kernel']->handle($request, $type, $catch);
    }

    /**
     * Handles onEarlyKernelRequest events.
     *
     * @param KernelEvent $event El evento a manipular
     */
    public function onEarlyKernelRequest(KernelEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            if (isset($this['exception_handler'])) {
                $this['dispatcher']->addSubscriber($this['exception_handler']);
            }
            $this['dispatcher']->addSubscriber(new ResponseListener($this['charset']));
        }
    }

    /**
     * Maneja eventos onKernelRequest.
     *
     * @param KernelEvent $event El evento a manipular
     */
    public function onKernelRequest(KernelEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->beforeDispatched = true;
            $this['dispatcher']->dispatch(SilexEvents::BEFORE, $event);
            $this['route_middlewares_trigger']($event);
        }
    }

    /**
     * Manipula convertidores.
     *
     * @param FilterControllerEvent $event Una instancia de FilterControllerEvent
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $route = $this['routes']->get($request->attributes->get('_route'));
        if ($route && $converters = $route->getOption('_converters')) {
            foreach ($converters as $name => $callback) {
                $request->attributes->set($name, call_user_func($callback, $request->attributes->get($name, null), $request));
            }
        }
    }

    /**
     * Manipula respuestas de cadena.
     *
     * Controlador para onKernelView.
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $response = $event->getControllerResult();
        $converter = new StringResponseConverter();
        $event->setResponse($converter->convert($response));
    }

    /**
     * Ejecuta filtros after.
     *
     * Handler for onKernelResponse.
     */
    public function onKernelResponse(Event $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this['dispatcher']->dispatch(SilexEvents::AFTER, $event);
        }
    }

    /**
     * Executes registered error handlers until a response is returned,
     * in which case it returns it to the client.
     *
     * Handler for onKernelException.
     *
     * @see error()
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$this->beforeDispatched) {
            $this->beforeDispatched = true;
            $this['dispatcher']->dispatch(SilexEvents::BEFORE, $event);
        }

        $errorEvent = new GetResponseForErrorEvent($this, $event->getRequest(), $event->getRequestType(), $event->getException());
        $this['dispatcher']->dispatch(SilexEvents::ERROR, $errorEvent);

        if ($errorEvent->hasResponse()) {
            $event->setResponse($errorEvent->getResponse());
        }
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST    => array(
                array('onEarlyKernelRequest', 256),
                array('onKernelRequest')
            ),
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE   => 'onKernelResponse',
            KernelEvents::EXCEPTION  => 'onKernelException',
            KernelEvents::VIEW       => array('onKernelView', -10),
        );
    }
}

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
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
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
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Silex\RedirectableUrlMatcher;
use Silex\ControllerResolver;

/**
 * La clase de la plataforma Silex.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Application extends \Pimple implements HttpKernelInterface, EventSubscriberInterface, TerminableInterface
{
    const VERSION = '1.0-DEV';

    private $providers = array();
    private $booted = false;
    private $beforeDispatched = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $app = $this;

        $this['logger'] = null;

        $this['autoloader'] = function () {
            throw new \RuntimeException('You tried to access the autoloader service. The autoloader has been removed from Silex. It is recommended that you use Composer to manage your dependencies and handle your autoloading. See http://getcomposer.org for more information.');
        };

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
            $dispatcher->addSubscriber(new RouterListener($urlMatcher, $app['logger']));
            $dispatcher->addSubscriber(new LocaleListener($app['locale'], $urlMatcher));

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

        $this['route_before_middlewares_trigger'] = $this->protect(function (GetResponseEvent $event) use ($app) {
            $request = $event->getRequest();
            $routeName = $request->attributes->get('_route');
            if (!$route = $app['routes']->get($routeName)) {
                return;
            }

            foreach ((array) $route->getOption('_before_middlewares') as $callback) {
                $ret = call_user_func($callback, $request);
                if ($ret instanceof Response) {
                    $event->setResponse($ret);

                    return;
                } elseif (null !== $ret) {
                    throw new \RuntimeException(sprintf('A before middleware for route "%s" returned an invalid response value. Must return null or an instance of Response.', $routeName));
                }
            }
        });

        $this['route_after_middlewares_trigger'] = $this->protect(function (FilterResponseEvent $event) use ($app) {
            $request = $event->getRequest();
            $routeName = $request->attributes->get('_route');
            if (!$route = $app['routes']->get($routeName)) {
                return;
            }

            foreach ((array) $route->getOption('_after_middlewares') as $callback) {
                $response = call_user_func($callback, $request, $event->getResponse());
                if ($response instanceof Response) {
                    $event->setResponse($response);
                } elseif (null !== $response) {
                    throw new \RuntimeException(sprintf('An after middleware for route "%s" returned an invalid response value. Must return null or an instance of Response.', $routeName));
                }
            }
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
        $this['dispatcher']->addListener(SilexEvents::FINISH, function (PostResponseEvent $event) use ($callback) {
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
        $this['dispatcher']->addListener(SilexEvents::ERROR, function (GetResponseForExceptionEvent $event) use ($callback) {
            $exception = $event->getException();

            if (is_array($callback)) {
                $callbackReflection = new \ReflectionMethod($callback[0], $callback[1]);
            } elseif (is_object($callback) && !$callback instanceof \Closure) {
                $callbackReflection = new \ReflectionObject($callback);
                $callbackReflection = $callbackReflection->getMethod('__invoke');
            } else {
                $callbackReflection = new \ReflectionFunction($callback);
            }

            if ($callbackReflection->getNumberOfParameters() > 0) {
                $parameters = $callbackReflection->getParameters();
                $expectedException = $parameters[0];
                if ($expectedException->getClass() && !$expectedException->getClass()->isInstance($exception)) {
                    return;
                }
            }

            $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

            $result = call_user_func($callback, $exception, $code);

            if (null !== $result) {
                R$response = $result instanceof Response ? $result : new Response((string) $result);

                $event->setResponse($response);
            }
        }, $priority);
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
     * @param array   $headers  Una matriz de cabeceras de la respuesta
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
     * @param array   $headers Una matriz de cabeceras de la respuesta
     *
     * @see JsonResponse
     */
    public function json($data = array(), $status = 200, $headers = array())
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Monta una aplicación bajo el prefijo de ruta dado.
     *
     * @param string                                           $prefix The route prefix
     * @param ControllerCollection|ControllerProviderInterface $app    A ControllerCollection or a ControllerProviderInterface instance
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

        $this->beforeDispatched = false;

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

    /**
     * Handles KernelEvents::REQUEST events registered early.
     *
     * @param GetResponseEvent $event The event to handle
     */
    public function onEarlyKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            if (isset($this['exception_handler'])) {
                $this['dispatcher']->addSubscriber($this['exception_handler']);
            }
            $this['dispatcher']->addSubscriber(new ResponseListener($this['charset']));
        }
    }

    /**
     * Runs before filters.
     *
     * @param GetResponseEvent $event The event to handle
     *
     * @see before()
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this['locale'] = $event->getRequest()->getLocale();

        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->beforeDispatched = true;
            $this['dispatcher']->dispatch(SilexEvents::BEFORE, $event);
        }

        $this['route_before_middlewares_trigger']($event);
    }

    /**
     * Manipula convertidores.
     *
     * @param FilterControllerEvent $event The event to handle
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
     * @param GetResponseForControllerResultEvent $event The event to handle
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $response = $event->getControllerResult();
        $response = $response instanceof Response ? $response : new Response((string) $response);

        $event->setResponse($response);
    }

    /**
     * Ejecuta filtros after.
     *
     * @param FilterResponseEvent $event The event to handle
     *
     * @see after()
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $this['route_after_middlewares_trigger']($event);

        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this['dispatcher']->dispatch(SilexEvents::AFTER, $event);
        }
    }

    /**
     * Runs finish filters.
     *
     * @param PostResponseEvent $event The event to handle
     *
     * @see finish()
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this['dispatcher']->dispatch(SilexEvents::FINISH, $event);
    }

    /**
     * Runs error filters.
     *
     * Executes registered error handlers until a response is returned,
     * in which case it returns it to the client.
     *
     * @param GetResponseForExceptionEvent $event The event to handle
     *
     * @see error()
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$this->beforeDispatched) {
            $this->beforeDispatched = true;
            try {
                $this['dispatcher']->dispatch(SilexEvents::BEFORE, $event);
            } catch (\Exception $e) {
                // as we are already handling an exception, ignore this one
                // even if it might have been thrown on purpose by the developer
            }
        }

        $errorEvent = new GetResponseForExceptionEvent($this, $event->getRequest(), $event->getRequestType(), $event->getException());
        $this['dispatcher']->dispatch(SilexEvents::ERROR, $errorEvent);

        if ($errorEvent->hasResponse()) {
            $event->setResponse($errorEvent->getResponse());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST    => array(
                array('onEarlyKernelRequest', 256),
                array('onKernelRequest')
            ),
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE   => 'onKernelResponse',
            KernelEvents::EXCEPTION  => array('onKernelException', -10),
            KernelEvents::TERMINATE  => 'onKernelTerminate',
            KernelEvents::VIEW       => array('onKernelView', -10),
        );
    }
}

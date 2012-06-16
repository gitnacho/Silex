``SecurityServiceProvider``
===========================

El ``SecurityServiceProvider`` gestiona la authentication y autorización de tus aplicaciones.

Parámetros
----------

n/a

Servicios
---------

* **security.context**: El punto de entrada principal al proveedor de seguridad. Utilízalo para obtener la ficha del usuario actual.

* **security.authentication_manager**: Una instancia de `AuthenticationProviderManager <http://api.symfony.com/master/Symfony/Component/Security/Core/Authentication/AuthenticationProviderManager.html>`_, responsable de la autenticación.

* **security.access_manager**: Una instancia de `AccessDecisionManager <http://api.symfony.com/master/Symfony/Component/Security/Core/Authorization/AccessDecisionManager.html>`_, responsable de la autorización.

* **security.session_strategy**: Define la estrategia utilizada para autenticación de la sesión (de manera predefinida es una estrategia de migración).

* **security.user_checker**: Comprueba los indicadores del usuario después de su autenticación.

* **security.last_error**: Devuelve los últimos errores de autenticación dados a un objeto ``Petición``.

* **security.encoder_factory**: Define las estrategias de codificación para la contraseña de los usuarios (de manera predeterminada utiliza una algoritmo de suma de comprobación para todos los  usuarios).

.. note::

    El proveedor de servicios define muchos otros servicios que está utilizando internamente pero raramente se necesita personalizarlos.

Registrando
-----------

.. code-block:: php

    $app->register(new Silex\Provider\SecurityServiceProvider());

.. note::

    El Componente ``Security`` de *Symfony* viene con el archivo "gordo" de *Silex* pero no en el normal. Si estás usando ``Composer``, añádelo como dependencia a tu archivo ``composer.json``:

    .. code-block:: json

        "require": {
            "symfony/security": "2.1.*"
        }

Uso
---

El componente ``Security`` de *Symfony* es muy potente. Para aprender más sobre él, lee la `documentación de Seguridad de Symfony2 <http://gitnacho.github.com/symfony-docs-es/book/security.html>`_.

.. tip::

    Cuándo una configuración de seguridad  no se comporta como se espera, habilita la anotación cronológica de eventos (con la extensión ``Monolog``, por ejemplo) como el registrador del componente de Seguridad con mucha información interesante sobre qué hace y por qué.

Abajo hay una lista de recetas que cubren algunos casos de uso comunes.

Accediendo al usuario actual
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

La información del usuario actual está almacenada en una ficha accesible vía el servicio ``security.context``::

    $token = $app['security.context']->getToken();

Si no hay ninguna información sobre el usuario, la ficha es ``null``. Si el usuario es conocido, lo puedes recuperar con una llamada a ``getUser()``::

    if (null !== $token) {
        $user = $token->getUser();
    }

El usuario puede ser una cadena, y objeto con un método ``_toString()``, o una instancia de `UserInterface <http://api.symfony.com/master/Symfony/Component/Security/Core/User/UserInterface.html>`_.

Asegurando una ruta con autenticación *HTTP*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

La siguiente configuración de autenticación *HTTP* básica para asegurar direcciones *URL* bajo ``/admin/``::

    $app['security.firewalls'] = array(
        'admin' => array(
            'pattern' => '^/admin/',
            'http' => true,
            'users' => array(
                // la contraseña cruda es foo
                'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
            ),
        ),
    );

``pattern`` es una expresión regular; la opción ``http`` le dice a la capa de seguridad que utilice autenticación *HTTP* básica y opción ``users`` define usuarios válidos.

Cada usuario está definido con la siguiente información:

* El rol o un arreglo de roles para el usuario (los roles son cadenas que empiezan con ``ROLE_`` y acaban con cualquier cosa que quieras);

* La contraseña codificada del usuario.

.. caution::

    Todos los usuarios cuando menos deben tener un rol asociado a ellos.

La configuración predeterminada de la extensión obliga a codificar las contraseñas. Para generar una contraseña codificada válida desde una contraseña cruda, usa el servicio ``security.encoder``::

    // Encuentra la contraseña codificada para foo
    $password = $app['security.encoder']->encodePassword('foo', null);

El segundo argumento es la sal a utilizar para el usuario (de manera predeterminada es ``null``).

Cuándo el usuario está autenticado, el usuario almacenado en la ficha es una instancia de `User <http://api.symfony.com/master/Symfony/Component/Security/Core/User/User.html>`_

Asegurando una ruta con un formulario
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Utilizar un formulario para autenticar usuarios es muy similar a la configuración anterior.
En vez de utilizar la versión ``http``, usa ``form`` y define estos dos parámetros:

* **login_path**: La ruta de inicio de sesión a dónde el usuario es redirigido cuándo está accediendo a una área asegurada sin estar autenticado de modo que pueda introducir sus credenciales;

* **check_path**: La *URL* utilizada por *Symfony* para validar las credenciales del usuario.

Aquí tienes cómo para asegurar con una formulario todas las direcciones *URL* bajo ``/admin/``::

    $app['security.firewalls'] = array(
        'admin' => array(
            'pattern' => '^/admin/',
            'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
            'users' => array(
                'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
            ),
        ),
    );

Siempre ten en cuenta las siguientes dos reglas de oro:

* La ruta ``login_path`` siempre se tiene que definido **fuera** del área asegurada (o si está en el área asegurada, tienes que habilitar el mecanismo de autenticación ``anónimo`` -- ve más abajo);

* La ruta ``check_path`` siempre se debe definir **dentro** del área asegurada.

Para que trabaje el formulario de inicio de sesión, crea un controlador donde inicies la sesión::

    $app->get('/login', function(Request $request) use ($app) {
        $app['session']->start();

        return $app['twig']->render('login.html', array(
            'error'         => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
        ));
    });

Las variables ``error`` y ``last_username`` contienen el último error de autenticación y el último nombre de usuario introducido por el usuario en caso de un error de autenticación.

Crea la plantilla asociada:

.. code-block:: jinja

    <form action="{{ path('admin_login_check') }}" method="post">
        {{ error }}
        <input type="text" name="_username" value="{{ last_username }}" />
        <input type="password" name="_password" value="" />
        <input type="submit" />
    </form>

.. note::

    La ruta ``admin_login_check`` la define *Silex* automáticamente y su nombre se deriva del valor de ``check_path`` (todas las ``/`` son reemplazadas con ``_`` y se quita la ``/`` inicial).

Definiendo más de un cortafuegos
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

No estás limitado a definir un cortafuegos por proyecto.

Configurar varios cortafuegos es útil cuándo  quieres proteger diferentes partes de tu sitio *web* con diferentes estrategias de autenticación o para diferentes usuarios (como usar autenticación *HTTP* básica para la *API* del sitio *web* y un formulario para proteger tu área de administración del sitio *web*).

También es útil cuándo quieres proteger todas las direcciones *URL* excepto el formulario de inicio de sesión::

    $app['security.firewalls'] = array(
        'login' => array(
            'pattern' => '^/login$',
        ),
        'secured' => array(
            'pattern' => '^.*$',
            'form' => array('login_path' => '/login', 'check_path' => '/login_check'),
            'users' => array(
                'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
            ),
        ),
    );

El orden de los cortafuegos configurados es significativo puesto que gana el primero que coincida. En la configuración anterior el primero garantiza que la *URL* ``/login`` no está protegida (ningún ajuste de autenticación), y luego protege todas las otras *URL*.

Añadiendo un cierre de sesión
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Cuándo utilizas un formulario para autenticación, puedes permitir a los usuarios cerrar su sesión si añades la opción ``logout``::

    $app['security.firewalls'] = array(
        'secured' => array(
            'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
            'logout' => array('logout_path' => '/logout'),

            // ...
        ),
    );

Automáticamente se genera una ruta, basándose en la ruta configurada (todas las ``/`` se reemplazan con ``_`` y se quita la ``/`` inicial):

.. code-block:: jinja

    <a href="{{ path('logout') }}">Logout</a>

Permitiendo usuarios anónimos
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Cuándo proteges sólo algunas partes de tu sitio *web*, la información de usuario no está disponible en áreas no protegidas. Para hacer accesible el usuario en tales áreas, habilita el mecanismo de autenticación ``anónimo``::

    $app['security.firewalls'] = array(
        'unsecured' => array(
            'anonymous' => true,

            // ...
        ),
    );

Cuándo habilitas la opción anónimo, siempre será accesible el usuario desde el contexto de seguridad; Si el usuario no está autenticado, regresa la cadena ``anon``.

Comprobando roles del usuario
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Para comprobar si un usuario tiene algún rol, usa el método ``isGranted()`` en el contexto de seguridad::

    if ($app['security.context']->isGranted('ROLE_ADMIN') {
        // ...
    }

También puedes comprobar roles en las plantillas *Twig*:

.. code-block:: jinja

    {% if is_granted('ROLE_ADMIN') %}
        <a href="/secured?_switch_user=fabien">Switch to Fabien</a>
    {% endif %}

Puede comprobar si un usuario está "plenamente autenticado" (no es un usuario anónimo, por ejemplo) con el rol especial ``IS_AUTHENTICATED_FULLY``:

.. code-block:: jinja

    {% if is_granted('IS_AUTHENTICATED_FULLY') %}
        <a href="{{ path('logout') }}">Logout</a>
    {% else %}
        <a href="{{ path('login') }}">Login</a>
    {% endif %}

.. tip::

    No uses el método ``getRoles()`` para comprobar los roles del usuario.

.. caution::

    ``isGranted()`` lanza una excepción cuándo no hay disponible información de autenticación (que es el caso anterior del área no protegida).

Suplantando a un usuario
~~~~~~~~~~~~~~~~~~~~~~~~

Si quieres ser capaz de cambiar a otro usuario (sin el conocer las credenciales del usuario), habilita la estrategia de autenticación ``switch_user``::

    $app['security.firewalls'] = array(
        'unsecured' => array(
            'switch_user' => array('parameter' => '_switch_user', 'role' => 'ROLE_ALLOWED_TO_SWITCH'),

            // ...
        ),
    );

Cambiar a otro usuario ahora es cuestión de añadir el parámetro de consulta ``_switch_user`` a cualquier *URL* cuándo estés conectado como un usuario que tiene el rol ``ROLE_ALLOWED_TO_SWITCH``:

.. code-block:: jinja

    {% if is_granted('ROLE_ALLOWED_TO_SWITCH') %}
        <a href="?_switch_user=fabien">Switch to user Fabien</a>
    {% endif %}

Puedes comprobar que estás suplantando a un usuario comprobando el rol especial ``ROLE_PREVIOUS_ADMIN``. Esto es útil por ejemplo, para permitir que el usuario regrese a su primer cuenta:

.. code-block:: jinja

    {% if is_granted('ROLE_PREVIOUS_ADMIN') %}
        Eres un administrador pero has suplantado a otro usuario,
        <a href="?_switch_user=_exit">salir</a> para cambiar.
    {% endif %}

Definiendo una jerarquía de roles
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Definir una jerarquía de roles te permite garantizar automáticamente algunos roles adicionales a los usuarios::

    $app['security.role_hierarchy'] = array(
        'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
    );

Con esta configuración, todos los usuarios con el rol ``ROLE_ADMIN`` además, automáticamente tener los roles ``ROLE_USER`` y ``ROLE_ALLOWED_TO_SWITCH``.

Definiendo reglas de acceso
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Los roles son una gran manera de adaptar el comportamiento de tu sitio *web* que depende de grupos de usuarios, pero también se suele usar para proteger algunas áreas definiendo reglas de acceso::

    $app['security.access_rules'] = array(
        array('^/admin', 'ROLE_ADMIN', 'https'),
        array('^.*$', 'ROLE_USER'),
    );

Con la configuración anterior, los usuarios deben tener el rol ``ROLE_ADMIN`` para acceder a la sección ``/admin`` del sitio *web*, y ``ROLE_USER`` para todo lo demás.
Además, la sección ``admin`` sólo se puede acceder vía *HTTPS* (si este no es el caso, el usuario será redirigido automáticamente).

Definiendo un proveedor de usuario personalizado
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Utilizar una matriz de usuarios es sencillo y útil cuándo proteges una sección ``admin`` de un sitio *web* personal, pero puedes sustituir este mecanismo predeterminado con el tuyo propio.

La opción ``users`` se puede definir como servicio que regresa una instancia de `UserProvider <http://api.symfony.com/master/Symfony/Component/Security/Core/User/UserProviderInterface.html>`_::

    'users' => $app->share(function () use ($app) {
        return new UserProvider($app['db']);
    }),

Aquí está un sencillo ejemplo de un proveedor de usuarios, donde se usa el *DBAL* de *Doctrine* para almacenar a los usuarios::

    use Symfony\Component\Security\Core\User\UserProviderInterface;
    use Symfony\Component\Security\Core\User\UserInterface;
    use Symfony\Component\Security\Core\User\User;
    use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\Schema\Table;

    class UserProvider implements UserProviderInterface
    {
        private $conn;

        public function __construct(Connection $conn)
        {
            $this->conn = $conn;
        }

        public function loadUserByUsername($username)
        {
            $stmt = $this->conn->executeQuery('SELECT * FROM users WHERE username = ?', array(strtolower($username)));

            if (!$user = $stmt->fetch()) {
                throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
            }

            return new User($user['username'], $user['password'], explode(',', $user['roles']), true, true, true, true);
        }

        public function refreshUser(UserInterface $user)
        {
            if (!$user instanceof User) {
                throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
            }

            return $this->loadUserByUsername($user->getUsername());
        }

        public function supportsClass($class)
        {
            return $class === 'Symfony\Component\Security\Core\User\User';
        }
    }

En este ejemplo, las instancias de la clase ``User`` son creadas por los usuarios, pero  puedes definir tu propia clase; El único requisito es que la clase debe implementar la `UserInterface <http://api.symfony.com/master/Symfony/Component/Security/Core/User/UserInterface.html>`_

Y aquí está el código que puedes utilizar para crear el esquema de la base de datos y algunos usuarios de ejemplo::

    $schema = $conn->getSchemaManager();
    if (!$schema->tablesExist('users')) {
        $users = new Table('users');
        $users->addColumn('id', 'integer', array('unsigned' => true));
        $users->setPrimaryKey(array('id'));
        $users->addColumn('username', 'string', array('length' => 32));
        $users->addUniqueIndex(array('username'));
        $users->addColumn('password', 'string', array('length' => 255));
        $users->addColumn('roles', 'string', array('length' => 255));

        $schema->createTable($users);

        $this->conn->executeQuery('INSERT INTO users (username, password, roles) VALUES ("fabien", "5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==", "ROLE_USER")');
        $this->conn->executeQuery('INSERT INTO users (username, password, roles) VALUES ("admin", "5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==", "ROLE_ADMIN")');
    }

.. tip::

    Si estás utilizando el *ORM* de *Doctrine*, el puente de *Symfony* para *Doctrine* proporciona una clase proveedora del usuarios que es capaz de cargar usuarios desde tus entidades.

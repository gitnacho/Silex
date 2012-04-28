
.. code-block:: php

    <?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Este archivo fuente está sujeto a la licencia MIT incluida con
 * este código fuente en el archivo LICENSE.
 */

namespace Silex\Tests\Provider;

use Silex\Application;
use Silex\Provider\SessionServiceProvider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * SessionProvider test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class SessionServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();

        $app->register(new SessionServiceProvider());

        $app['session.storage'] = $app->share(function () use ($app) {
            return new MockArraySessionStorage();
        });

        $app->get('/login', function () use ($app) {
            $app['session']->set('logged_in', true);
            return 'Logged in successfully.';
        });

        $app->get('/account', function () use ($app) {
            if (!$app['session']->get('logged_in')) {
                return 'You are not logged in.';
            }

            return 'This is your account.';
        });

        $request = Request::create('/login');
        $response = $app->handle($request);
        $this->assertEquals('Logged in successfully.', $response->getContent());

        $request = Request::create('/account');
        $response = $app->handle($request);
        $this->assertEquals('This is your account.', $response->getContent());
    }
}

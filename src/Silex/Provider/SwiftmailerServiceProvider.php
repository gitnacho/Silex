<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Para información completa sobre los derechos de autor y licencia, por
 * favor, ve el archivo LICENSE que viene con este código fuente.
 */

namespace Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Swiftmailer Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SwiftmailerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['swiftmailer.options'] = array();

        $app['mailer.initialized'] = false;

        $app['mailer'] = $app->share(function ($app) {
            $app['mailer.initialized'] = true;

            return new \Swift_Mailer($app['swiftmailer.spooltransport']);
        });

        $app['swiftmailer.spooltransport'] = $app->share(function ($app) {
            return new \Swift_SpoolTransport($app['swiftmailer.spool']);
        });

        $app['swiftmailer.spool'] = $app->share(function ($app) {
            return new \Swift_MemorySpool();
        });

        $app['swiftmailer.transport'] = $app->share(function ($app) {
            $transport = new \Swift_Transport_EsmtpTransport(
                $app['swiftmailer.transport.buffer'],
                array($app['swiftmailer.transport.authhandler']),
                $app['swiftmailer.transport.eventdispatcher']
            );

            $options = $app['swiftmailer.options'] = array_replace(array(
                'host'       => 'localhost',
                'port'       => 25,
                'username'   => '',
                'password'   => '',
                'encryption' => null,
                'auth_mode'  => null,
            ), $app['swiftmailer.options']);

            $transport->setHost($options['host']);
            $transport->setPort($options['port']);
            $transport->setEncryption($options['encryption']);
            $transport->setUsername($options['username']);
            $transport->setPassword($options['password']);
            $transport->setAuthMode($options['auth_mode']);

            return $transport;
        });

        $app['swiftmailer.transport.buffer'] = $app->share(function () {
            return new \Swift_Transport_StreamBuffer(new \Swift_StreamFilters_StringReplacementFilterFactory());
        });

        $app['swiftmailer.transport.authhandler'] = $app->share(function () {
            return new \Swift_Transport_Esmtp_AuthHandler(array(
                new \Swift_Transport_Esmtp_Auth_CramMd5Authenticator(),
                new \Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
                new \Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
            ));
        });

        $app['swiftmailer.transport.eventdispatcher'] = $app->share(function () {
            return new \Swift_Events_SimpleEventDispatcher();
        });
    }

    public function boot(Application $app)
    {
        // BC: to be removed before 1.0
        if (isset($app['swiftmailer.class_path'])) {
            throw new \RuntimeException('You have provided the swiftmailer.class_path parameter. Se ha eliminado de Silex el cargador automático. Se recomienda utilizar Composer para gestionar tus dependencias y manejar tu carga automática. If you are already using Composer, you can remove the parameter. Ve http://getcomposer.org para más información.');
        }

        $app->finish(function () use ($app) {
            // To speed things up (by avoiding Swift Mailer initialization), flush
            // messages only if our mailer has been created (potentially used)
            if ($app['mailer.initialized']) {
                $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);
            }
        });
    }
}

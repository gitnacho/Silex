<?php

/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Para información completa sobre los derechos de autor y licencia, por
 * favor, ve el archivo LICENSE que viene con este código fuente.
 */

namespace Silex\Util;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;

/**
 * La clase Compiler compila la plataforma Silex.
 *
 * This is deprecated. Use composer instead.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Compiler
{
    protected $version;

    /**
     * Compila el código fuente de Silex en un único archivo Phar.
     *
     * @param string $pharFile Nombre del archivo Phar producido
     */
    public function compile($pharFile = 'silex.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $process = new Process('git log --pretty="%h %ci" -n1 HEAD');
        if ($process->run() > 0) {
            throw new \RuntimeException('No puedo encontrar el binario de git.');
        }
        $this->version = trim($process->getOutput());

        $phar = new \Phar($pharFile, 0, 'silex.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $root = __DIR__.'/../../..';

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->exclude('Tests')
            ->in($root.'/src')
            ->in($root.'/vendor/pimple/pimple/lib')
            ->in($root.'/vendor/symfony/event-dispatcher/Symfony/Component/EventDispatcher')
            ->in($root.'/vendor/symfony/http-foundation/Symfony/Component/HttpFoundation')
            ->in($root.'/vendor/symfony/http-kernel/Symfony/Component/HttpKernel')
            ->in($root.'/vendor/symfony/routing/Symfony/Component/Routing')
            ->in($root.'/vendor/symfony/browser-kit/Symfony/Component/BrowserKit')
            ->in($root.'/vendor/symfony/css-selector/Symfony/Component/CssSelector')
            ->in($root.'/vendor/symfony/dom-crawler/Symfony/Component/DomCrawler')
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo($root.'/LICENSE'), false);
        $this->addFile($phar, new \SplFileInfo($root.'/vendor/autoload.php'));
        $this->addFile($phar, new \SplFileInfo($root.'/vendor/composer/ClassLoader.php'));
        $this->addFile($phar, new \SplFileInfo($root.'/vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new \SplFileInfo($root.'/vendor/composer/autoload_classmap.php'));

        // Cooperantes
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        // $phar->compressFiles(\Phar::GZ);

        unset($phar);
    }

    protected function addFile($phar, $file, $strip = true)
    {
        $path = str_replace(dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR, '', $file->getRealPath());

        $content = file_get_contents($file);
        if ($strip) {
            $content = self::stripWhitespace($content);
        }

        $content = preg_replace("/const VERSION = '.*?';/", "const VERSION = '".$this->version."';", $content);

        $phar->addFromString($path, $content);
    }

    protected function getStub()
    {
        return <<<'EOF'
<?php
/*
 * Este archivo es parte de la plataforma Silex.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Este archivo fuente está sujeto a la licencia MIT incluida con
 * este código fuente en el archivo LICENSE.
 */

Phar::mapPhar('silex.phar');

require_once 'phar://silex.phar/vendor/autoload.php';

if ('cli' === php_sapi_name() && basename(__FILE__) === basename($_SERVER['argv'][0]) && isset($_SERVER['argv'][1])) {
    switch ($_SERVER['argv'][1]) {
        case 'update':
            $remoteFilename = 'http://silex.sensiolabs.org/get/silex.phar';
            $localFilename = __DIR__.'/silex.phar';

            file_put_contents($localFilename, file_get_contents($remoteFilename));
            break;

        case 'check':
            $latest = trim(file_get_contents('http://silex.sensiolabs.org/get/version'));

            if ($latest != Silex\Application::VERSION) {
                printf("Hay disponible una nueva versión de Silex (%s).\n", $latest);
            } else {
                print("Estás usando la versión más reciente de Silex.\n");
            }
            break;

        case 'version':
            printf("Silex versión %s\n", Silex\Application::VERSION);
            break;

        default:
            printf("Orden desconocida '%s' (ordenes disponibles: version, check y update).\n", $_SERVER['argv'][1]);
    }

    exit(0);
}

__HALT_COMPILER();
EOF;
    }

    /**
     * Quita espacios en blanco de una cadena fuente PHP mientras
     * preserva los números de línea.
     *
     * Basado en Kernel::stripComments(), pero deja intactos los números de línea.
     *
     * @param string $source Una cadena PHP
     *
     * @return string The PHP string with the whitespace removed
     */
    public static function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce espacios amplios
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normaliza nuevas línea a \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // recorta espacios en los extremos
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }
}

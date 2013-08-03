<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX;

use Zend\Loader\StandardAutoloader;
use Tillikum\Bootstrap as TillikumBootstrap;

class Bootstrap extends TillikumBootstrap
{
    public function _initExtensionAutoloader()
    {
        $extensionBasePath = __DIR__;
        $uhdsBasePath = stream_resolve_include_path('Uhds');

        $autoloader = new StandardAutoloader(
            array(
                'namespaces' => array(
                    'TillikumX' => $extensionBasePath,
                    'Uhds' => $uhdsBasePath,
                ),
                /**
                 * @todo remove once all classes are converted to real namespaces
                 */
                'prefixes' => array(
                    'TillikumX' => $extensionBasePath,
                    'Uhds' => $uhdsBasePath,
                ),
            )
        );

        $autoloader->register();
    }

    public function _initUtils()
    {
        if (!function_exists('id')) {
            require 'utils.php';
        }
    }
}

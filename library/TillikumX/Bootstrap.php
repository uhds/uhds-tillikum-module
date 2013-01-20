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

    public function _initErrorHandler()
    {
        if (!(bool) ini_get('display_errors')) {
            set_error_handler(
                $this->createErrorHandler()
            );
        }
    }

    public function _initExceptionHandler()
    {
        if (!(bool) ini_get('display_errors')) {
            set_exception_handler(
                $this->createExceptionHandler()
            );
        }
    }

    public function _initUtils()
    {
        if (!function_exists('id')) {
            require 'utils.php';
        }
    }

    protected function createErrorHandler()
    {
        return function($errno, $errstr, $errfile, $errline, $errcontext) {
            if (!(error_reporting() & $errno)) {
                return false;
            }

            $summary = sprintf(
                '%s%s%s',
                isset($errfile) ? $errfile : '',
                isset($errline) ? "({$errline}): " : '',
                $errstr
            );

            if (in_array($errno, array(E_NOTICE, E_USER_NOTICE))) {
                openlog('uhds-error-handler', LOG_NDELAY, LOG_LOCAL0);
                syslog(LOG_NOTICE, $summary);
                closelog();

                return true;
            }

            $lineTemplate = "#%s %s%s%s(%s)\n";

            $body = "Call trace (most recent call first):\n\n";
            foreach (debug_backtrace() as $frameIndex => $frame) {
                if ($frameIndex === 0) {
                    $body .= "#{$frameIndex} (error handler)\n";

                    continue;
                }

                $callString = sprintf(
                    '%s%s%s',
                    isset($frame['class']) ? $frame['class'] : '',
                    isset($frame['type']) ? $frame['type'] : '',
                    isset($frame['function']) ? $frame['function'] : ''
                );

                $trimmedArgs = array();
                if (isset($frame['args'])) {
                    foreach ($frame['args'] as $arg) {
                        $trimmedArgs[] = gettype($arg);
                    }
                }

                $body .= sprintf(
                    $lineTemplate,
                    $frameIndex,
                    isset($frame['file']) ? $frame['file'] : '',
                    isset($frame['line']) ? "({$frame['line']}): " : '',
                    $callString,
                    implode(', ', $trimmedArgs)
                );
            }

            $constants = get_defined_constants(true);
            $constants = $constants['Core'];

            $errnoValuesToConstants = array();
            foreach ($constants as $constant => $value) {
                if (strpos($constant, 'E_') === 0) {
                    $errnoValuesToConstants[$value] = $constant;
                }
            }

            mail(
                'error-mail@uhds.oregonstate.edu',
                "[{$errnoValuesToConstants[$errno]}] {$summary}",
                $body
            );

            return true;
        };
    }

    protected function createExceptionHandler()
    {
        return function($exception) {
            $summary = sprintf(
                '%s%s%s',
                $exception->getFile(),
                $exception->getLine(),
                $exception->getMessage()
            );

            $lineTemplate = "#%s %s%s%s(%s)\n";

            $body = "Call trace (most recent call first)\n\n";
            foreach ($exception->getTrace() as $frameIndex => $frame) {
                $callString = sprintf(
                    '%s%s%s',
                    isset($frame['class']) ? $frame['class'] : '',
                    isset($frame['type']) ? $frame['type'] : '',
                    isset($frame['function']) ? $frame['function'] : ''
                );

                $trimmedArgs = array();
                if (isset($frame['args'])) {
                    foreach ($frame['args'] as $arg) {
                        $trimmedArgs[] = gettype($arg);
                    }
                }

                $body .= sprintf(
                    $lineTemplate,
                    $frameIndex,
                    isset($frame['file']) ? $frame['file'] : '',
                    isset($frame['line']) ? "({$frame['line']}): " : '',
                    $callString,
                    implode(', ', $trimmedArgs)
                );
            }

            $exceptionClass = get_class($exception);

            mail(
                'error-mail@uhds.oregonstate.edu',
                "[{$exceptionClass}] {$summary}",
                $body
            );

            return true;
        };
    }
}

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

/**
 * Bootstrap UHDS Tillikum extension
 */
class Bootstrap extends TillikumBootstrap
{
    public function _initExtensionAutoloader()
    {
        $extensionBasePath = __DIR__;

        $autoloader = new StandardAutoloader(
            array(
                'namespaces' => array(
                    'TillikumX' => $extensionBasePath,
                ),
                /**
                 * @todo remove once all classes are converted to real namespaces
                 */
                'prefixes' => array(
                    'TillikumX' => $extensionBasePath,
                ),
            )
        );
        $autoloader->register();
    }

    public function _initUhdsAutoloader()
    {
        $uhdsBasePath = stream_resolve_include_path('Uhds');

        $autoloader = new StandardAutoloader(
            array(
                'namespaces' => array(
                    'Uhds' => $uhdsBasePath,
                ),
                /**
                 * @todo remove once all classes are converted to real namespaces
                 */
                'prefixes' => array(
                    'Uhds' => $uhdsBasePath,
                ),
            )
        );
        $autoloader->register();
    }

    public function _initUhdsEntityManager()
    {
        $serviceManager = $this->bootstrap('ServiceManager')
            ->getResource('ServiceManager');

        $doctrineContainer = $this->bootstrap('Doctrine')
            ->getResource('Doctrine');

        $serviceManager->setService(
            'doctrine.entitymanager.orm_uhds',
            $doctrineContainer->getEntityManager('uhds')
        );
    }

    public function _initUhdsDependencyInjection()
    {
        $serviceManager = $this->bootstrap('ServiceManager')
            ->getResource('ServiceManager');

        $di = $serviceManager->get('Di');

        /*
         * Is there a better way to inject separate instances of the same
         * class like some of the report constructors have?
         */
        $uhdsEm = $serviceManager->get('doctrine.entitymanager.orm_uhds');

        $classes = [
            'TillikumX\Report\ApplicantCounts',
            'TillikumX\Form\Report\ApplicantCounts',
            'TillikumX\Report\AssignmentLetter',
            'TillikumX\Form\Report\AssignmentLetter',
            'TillikumX\Report\CancellationAudit',
            'TillikumX\Report\CheckinRoster',
            'TillikumX\Report\ContractAudit',
            'TillikumX\Form\Report\LivingLearning',
            'TillikumX\Report\LivingLearning',
            'TillikumX\Form\Report\NewAssignments',
            'TillikumX\Report\NewAssignments',
            'TillikumX\Report\Roster',
            'TillikumX\Report\Unassigned',
            'TillikumX\Form\Report\Unassigned',
        ];

        foreach ($classes as $class) {
            $di->instanceManager()->setParameters(
                $class,
                ['uhdsEm' => $uhdsEm]
            );
        }
    }
}

<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Authorization\RoleProvider;

use Tillikum\Authorization\RoleProvider\RoleProviderInterface;
use Zend\Permissions\Acl\Acl;

class RoleManager implements RoleProviderInterface
{
    protected $identity;
    protected $uhdsAcl;

    public function __construct($identity)
    {
        $this->identity = $identity;
        $this->uhdsAcl = new \Uhds_Acl();
    }

    public function configureAcl(Acl $acl)
    {
        $roles = $this->uhdsAcl->fetchUserRoles($this->identity, 'tillikum');

        foreach ($roles as $role) {
            switch ($role) {
                case '_root':
                case 'admin':
                    $acl->allow('_user');
                    break;
                case 'billing_module_read':
                case 'billing_module_write':
                    $acl->allow('_user', 'billing');
                    $acl->allow('_user', 'billing_navigation');
                    $acl->allow('_user', 'billing_event');
                    $acl->allow('_user', 'billing_invoice');
                    $acl->allow('_user', 'billing_rule');
                    break;
                case 'booking_module_read':
                    $acl->allow('_user', 'booking_navigation');
                    $acl->allow('_user', 'facility_booking', 'read');
                    break;
                case 'booking_module_write':
                    $acl->allow('_user', 'booking_navigation');
                    $acl->allow('_user', 'facility_booking');
                    break;
                case 'contract_module_read':
                    $acl->allow('_user', 'contract', 'read');
                    $acl->allow('_user', 'contract_signature', 'read');
                    break;
                case 'contract_module_write':
                    $acl->allow('_user', 'contract');
                    $acl->allow('_user', 'contract_signature');
                    break;
                case 'facility_module_read':
                    $acl->allow('_user', 'facility_navigation');
                    $acl->allow('_user', 'facility', 'read');
                    $acl->allow('_user', 'facility_config', 'read');
                    $acl->allow('_user', 'facility_hold', 'read');
                    $acl->allow('_user', 'facility_group', 'read');
                    $acl->allow('_user', 'facility_group_config', 'read');
                    break;
                case 'facility_module_write':
                    $acl->allow('_user', 'facility_navigation');
                    $acl->allow('_user', 'facility');
                    $acl->allow('_user', 'facility_config');
                    $acl->allow('_user', 'facility_hold');
                    $acl->allow('_user', 'facility_group');
                    $acl->allow('_user', 'facility_group_config');
                    break;
                case 'job_module_read':
                    $acl->allow('_user', 'job_navigation');
                    $acl->allow('_user', 'job', 'read');
                    break;
                case 'job_module_write':
                    $acl->allow('_user', 'job_navigation');
                    $acl->allow('_user', 'job');
                    break;
                case 'mealplan_module_read':
                    $acl->allow('_user', 'mealplan_booking', 'read');
                    break;
                case 'mealplan_module_write':
                    $acl->allow('_user', 'mealplan_booking');
                    break;
                case 'person_module_read':
                    $acl->allow('_user', 'person_navigation');
                    $acl->allow('_user', 'person', 'read');
                    break;
                case 'person_module_write':
                    $acl->allow('_user', 'person_navigation');
                    $acl->allow('_user', 'person');
                    break;
                case 'report_module_read':
                    $acl->allow('_user', 'report_navigation');
                    $acl->allow('_user', 'report', 'read');
                    break;
                case 'report_module_write':
                    $acl->allow('_user', 'report_navigation');
                    $acl->allow('_user', 'report');
                    break;
            }
        }
    }
}

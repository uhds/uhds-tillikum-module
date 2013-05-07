<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;

class DataTablePendingSummer2013 extends DataTablePendingSummerHousingApplication
{
    protected static $applicationPlansToTillikum = array(
        'basic' => 'Basic',
        'preferred' => 'Preferred',
        'premium' => 'Premium',
        'ultimate' => 'Ultimate'
    );

    public function dataTablePendingSummer2013()
    {
        return parent::dataTablePendingSummerHousingApplication();
    }

    public function direct()
    {
        return $this->dataTablePendingSummer2013();
    }

    public function getBookingStartDate()
    {
        return new DateTime('2013-06-16');
    }

    public function getBookingEndDate()
    {
        return new DateTime('2013-09-06');
    }

    public function getLastSpringBookingDate()
    {
        return new DateTime('2013-06-14');
    }

    public function getFirstFallBookingDate()
    {
        return new DateTime('2013-09-24');
    }

    public function fetchApplications()
    {
        $applicationGateway = new \Uhds\Model\HousingApplication\ApplicationGateway();

        return $applicationGateway->fetchProcessedByTemplateId('summer2013');
    }

    public function getBookingData($application)
    {
        $attendanceStart = empty($application->attendance->start) ? null : $application->attendance->start;
        $attendanceEnd = empty($application->attendance->end) ? null : $application->attendance->end;

        return array(
            'start' => $application->attendance->start,
            'end' => $application->attendance->end,
            'billing' => array(
                'rates' => array(
                    array(
                        'delete_me' => false,
                        'start' => $application->attendance->start,
                        'end' => $application->attendance->end,
                    )
                ),
            ),
        );
    }

    public function getMealplanData($application)
    {
        $ac = $this->_actionController;
        $em = $ac->getEntityManager();

        $mealplan = $em->find('Tillikum\Entity\Mealplan\Mealplan', 'Preferred');

        $attendanceStart = empty($application->attendance->start) ? null : $application->attendance->start;
        $attendanceEnd = empty($application->attendance->end) ? null : $application->attendance->end;

        return array(
            'mealplan_id' => $mealplan->id,
            'start' => $application->attendance->start,
            'end' => $application->attendance->end,
            'billing' => array(
                'rates' => array(
                    array(
                        'delete_me' => false,
                        'rule_id' => $mealplan->default_billing_rule->id,
                        'start' => $application->attendance->start,
                        'end' => $application->attendance->end,
                    )
                ),
            ),
        );
    }
}

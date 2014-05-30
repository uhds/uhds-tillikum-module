<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;
use Doctrine\ORM\EntityManager;

class DataTablePendingSummer2014 extends DataTablePendingSummerHousingApplication
{
    const TEMPLATE_ID = 27;

    public function dataTablePendingSummer2014()
    {
        return parent::dataTablePendingSummerHousingApplication();
    }

    public function direct()
    {
        return $this->dataTablePendingSummer2014();
    }

    public function getLastSpringBookingDate()
    {
        return new DateTime('2014-06-13');
    }

    public function getFirstFallBookingDate()
    {
        return new DateTime('2014-09-23');
    }

    public function getBookingStartDate()
    {
        return new DateTime('2014-06-15');
    }

    public function getBookingEndDate()
    {
        return new DateTime('2014-09-13');
    }

    public function fetchApplications()
    {
        $ac = $this->_actionController;
        $sm = $ac->getServiceManager();

        $uhdsEm = $sm->get('doctrine.entitymanager.orm_uhds');

        return $uhdsEm->createQuery(
            '
            SELECT a, c, s
            FROM Uhds\Entity\HousingApplication\Application\Application a
            LEFT JOIN a.completions c
            LEFT JOIN a.sections s
            WHERE a.template = :templateId AND a.state = :state
            '
        )
            ->setParameter('templateId', self::TEMPLATE_ID)
            ->setParameter('state', 'processed')
            ->getResult();
    }

    public function getBookingData($application)
    {
        $attendanceSection = $application->getSection('Attendance');

        $attendanceStart = empty($attendanceSection->getStart()) ? null : $attendanceSection->getStart();
        $attendanceEnd = empty($attendanceSection->getEnd()) ? null : $attendanceSection->getEnd();

        return [
            'start' => $attendanceStart->format('Y-m-d'),
            'end' => $attendanceEnd->format('Y-m-d'),
            'billing' => [
                'rates' => [
                    [
                        'delete_me' => false,
                        'start' => $attendanceStart->format('Y-m-d'),
                        'end' => $attendanceEnd->format('Y-m-d'),
                    ]
                ],
            ],
        ];
    }

    public function getMealplanData($application)
    {
        $ac = $this->_actionController;
        $sm = $ac->getServiceManager();

        $tillikumEm = $sm->get('doctrine.entitymanager.orm_default');

        $diningSection = $application->getSection('Dining');

        $plan = null;
        if ($diningSection) {
            $plan = $tillikumEm->find(
                'Tillikum\Entity\Mealplan\Mealplan',
                $diningSection->getPlanId()
            );
        }

        return [
            'mealplan_id' => $plan->id,
            'start' => '2014-06-15',
            'end' => '2014-09-06',
            'billing' => [
                'rates' => [
                    [
                        'delete_me' => false,
                        'rule_id' => $plan->default_billing_rule ? $plan->default_billing_rule->id : '',
                        'start' => '2014-06-15',
                        'end' => '2014-09-06',
                    ]
                ],
            ],
        ];
    }
}

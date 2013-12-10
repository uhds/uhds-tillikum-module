<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;
use Doctrine\ORM\EntityManager;

class DataTablePendingWinter2014 extends DataTablePendingHousingApplication
{
    public function dataTablePendingFall2013New()
    {
        return parent::dataTablePendingHousingApplication();
    }

    public function direct()
    {
        return $this->dataTablePendingFall2013New();
    }

    public function getBookingStartDate()
    {
        return new DateTime('2014-01-05');
    }

    public function getBookingEndDate()
    {
        return new DateTime('2014-06-13');
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
            ->setParameter('templateId', 2)
            ->setParameter('state', 'processed')
            ->getResult();
    }

    public function getBookingData($application)
    {
        return [
            'start' => '2014-01-05',
            'end' => '2014-06-13',
            'billing' => [
                'rates' => [
                    [
                        'delete_me' => false,
                        'start' => '2014-01-05',
                        'end' => '2014-03-21',
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
            'start' => '2014-01-05',
            'end' => '2014-03-21',
            'billing' => [
                'rates' => [
                    [
                        'delete_me' => false,
                        'rule_id' => $plan->default_billing_rule ? $plan->default_billing_rule->id : '',
                        'start' => '2014-01-05',
                        'end' => '2014-03-21',
                    ]
                ],
            ],
        ];
    }
}

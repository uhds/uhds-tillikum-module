<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;
use Doctrine\ORM\EntityManager;

class DataTablePendingFall2014Returners extends DataTablePendingHousingApplication
{
    const TEMPLATE_ID = 28;

    public function dataTablePendingFall2014Returners()
    {
        return parent::dataTablePendingHousingApplication();
    }

    public function direct()
    {
        return $this->dataTablePendingFall2014Returners();
    }

    public function getBookingStartDate()
    {
        return new DateTime('2014-09-23');
    }

    public function getBookingEndDate()
    {
        return new DateTime('2015-06-12');
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
        return [
            'start' => '2014-09-23',
            'end' => '2015-06-12',
            'billing' => [
                'rates' => [
                    [
                        'delete_me' => false,
                        'start' => '2014-09-28',
                        'end' => '2014-12-12',
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
            'start' => '2014-09-28',
            'end' => '2014-12-12',
            'billing' => [
                'rates' => [
                    [
                        'delete_me' => false,
                        'rule_id' => $plan->default_billing_rule ? $plan->default_billing_rule->id : '',
                        'start' => '2014-09-28',
                        'end' => '2014-12-12',
                    ]
                ],
            ],
        ];
    }
}
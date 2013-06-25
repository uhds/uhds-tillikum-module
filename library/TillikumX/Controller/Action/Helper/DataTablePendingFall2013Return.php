<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;

class DataTablePendingFall2013Return extends DataTablePendingHousingApplication
{
    protected static $applicationPlansToTillikum = array(
        'basic' => 'Basic',
        'preferred' => 'Preferred',
        'premium' => 'Premium',
        'ultimate' => 'Ultimate'
    );

    public function dataTablePendingFall2013Return()
    {
        return parent::dataTablePendingHousingApplication();
    }

    public function direct()
    {
        return $this->dataTablePendingFall2013Return();
    }

    public function getBookingStartDate()
    {
        return new DateTime('2013-09-24');
    }

    public function getBookingEndDate()
    {
        return new DateTime('2014-06-13');
    }

    public function fetchApplications()
    {
        $applicationGateway = new \Uhds\Model\HousingApplication\ApplicationGateway();

        return $applicationGateway->fetchProcessedByTemplateId('fall2013return');
    }

    public function getBookingData($application)
    {
        return array(
            'start' => '2013-09-24',
            'end' => '2014-06-13',
            'billing' => array(
                'rates' => array(
                    array(
                        'delete_me' => false,
                        'start' => '2013-09-24',
                        'end' => '2013-12-13',
                    )
                ),
            ),
        );
    }

    public function getMealplanData($application)
    {
        $ac = $this->_actionController;
        $em = $ac->getEntityManager();

        $plan = $rate = '';
        if (isset($application->dining)) {
            $planId = self::$applicationPlansToTillikum[$application->dining->plan];

            $mealplan = $em->find('Tillikum\Entity\Mealplan\Mealplan', $planId);
        }

        return array(
            'mealplan_id' => $mealplan->id,
            'start' => '2013-09-29',
            'end' => '2013-12-13',
            'billing' => array(
                'rates' => array(
                    array(
                        'delete_me' => false,
                        'rule_id' => $mealplan->default_billing_rule ? $mealplan->default_billing_rule->id : '',
                        'start' => '2013-09-29',
                        'end' => '2013-12-13',
                    )
                ),
            ),
        );
    }
}

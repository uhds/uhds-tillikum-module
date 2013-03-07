<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;

class DataTablePendingSpring2013 extends DataTablePendingHousingApplication
{
    protected static $applicationPlansToTillikum = array(
        'basic' => 'Basic',
        'preferred' => 'Preferred',
        'premium' => 'Premium',
        'ultimate' => 'Ultimate'
    );

    public function dataTablePendingWinter2013()
    {
        return parent::dataTablePendingHousingApplication();
    }

    public function direct()
    {
        return $this->dataTablePendingWinter2013();
    }

    public function getBookingStartDate()
    {
        return new DateTime('2013-03-29');
    }

    public function getBookingEndDate()
    {
        return new DateTime('2013-06-14');
    }

    public function fetchApplications()
    {
        $applicationGateway = new \Uhds\Model\HousingApplication\ApplicationGateway();

        return $applicationGateway->fetchProcessedByTemplateId('spring2013');
    }

    public function getBookingData($application)
    {
        return array(
            'start' => '2013-03-29',
            'end' => '2013-06-14',
            'billing' => array(
                'rates' => array(
                    array(
                        'delete_me' => false,
                        'start' => '2013-03-31',
                        'end' => '2013-06-14',
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
            'start' => '2013-03-31',
            'end' => '2013-06-14',
            'billing' => array(
                'rates' => array(
                    array(
                        'delete_me' => false,
                        'rule_id' => $mealplan->default_billing_rule ? $mealplan->default_billing_rule->id : '',
                        'start' => '2013-03-31',
                        'end' => '2013-06-14',
                    )
                ),
            ),
        );
    }
}

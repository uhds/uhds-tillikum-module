<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;

class DataTablePendingWinter2013 extends DataTablePendingHousingApplication
{
    protected static $applicationPlansToRatecodes = array(
        'basic' => 'HET4',
        'preferred' => 'HET3',
        'premium' => 'HET2',
        'ultimate' => 'HET1'
    );

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
        return new DateTime('2013-01-04');
    }

    public function getBookingEndDate()
    {
        return new DateTime('2013-06-14');
    }

    public function fetchApplications()
    {
        $applicationGateway = new \Uhds\Model\HousingApplication\ApplicationGateway();

        return $applicationGateway->fetchProcessedByTemplateId('winter2013');
    }

    public function getBookingData($application)
    {
        return array(
            'start' => '2013-01-04',
            'end' => '2013-06-14',
            'billing' => array(
                'rates' => array(
                    array(
                        'start' => '2013-01-06',
                        'end' => '2013-03-22',
                    )
                ),
            ),
        );
    }

    public function getMealplanData($application)
    {
        $ac = $this->_actionController;

        $plan = '';
        $rate = '';
        if (isset($application->dining)) {
            $plan = $application->dining->plan;
            if (array_key_exists($plan, self::$applicationPlansToRatecodes)) {
                $rate = self::$applicationPlansToRatecodes[$plan];
            }
        }

        // @todo remove reference to old_id
        $rule = $ac->getEntityManager()
            ->getRepository('Tillikum\Entity\Billing\Rule\Rule')
            ->findOneBy(
                array(
                    'old_id' => $rate,
                )
            );

        return array(
            'plan_id' => self::$applicationPlansToTillikum[$plan],
            'start' => '2013-01-06',
            'end' => '2013-03-22',
            'billing' => array(
                'rates' => array(
                    array(
                        'rule_id' => $rule ? $rule->id : '',
                        'start' => '2013-01-06',
                        'end' => '2013-03-22',
                    )
                ),
            ),
        );
    }
}

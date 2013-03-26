<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Form\Job;

use Tillikum\Form\Job\Job as JobForm;

class PropagateFacilityBookings extends JobForm
{
    public function init()
    {
        parent::init();

        $oldRateDate = new \Tillikum_Form_Element_Date(
            'old_rate_date',
            array(
                'description' => 'This is a date that helps select existing rates to copy when propagating rates.',
                'label' => 'What date do candidate rates currently pass through?',
                'required' => true,
            )
        );

        $newRateStartParameter = new \Tillikum_Form_Element_Date(
            'new_rate_start',
            array(
                'description' => 'This will be the start date of the new (propagated) rate.',
                'label' => 'New rate start date',
                'required' => true,
            )
        );

        $newRateEndParameter = new \Tillikum_Form_Element_Date(
            'new_rate_end',
            array(
                'description' => 'This will be the end date of the new (propagated) rate.',
                'label' => 'New rate end date',
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $oldRateDate,
                $newRateStartParameter,
                $newRateEndParameter,
            )
        );
    }
}

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

class FacilityBookingBilling extends JobForm
{
    public function init()
    {
        parent::init();

        $end = new \Tillikum_Form_Element_Date(
            'end',
            array(
                'description' => 'You should pick the latest possible date for this term (consider INTO) - remember that billing will not extend beyond the end of the rate.',
                'label' => 'What date do you want to bill through?',
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $end,
            )
        );
    }
}

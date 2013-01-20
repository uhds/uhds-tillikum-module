<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Form\Report;

use Tillikum\Form\Report\Report as ReportForm;

class BookingEndAudit extends ReportForm
{
    public function init()
    {
        parent::init();

        $rangeStart = new \Tillikum_Form_Element_Date(
            'range_start',
            array(
                'label' => 'What date is the earliest a booking included in this report can end?',
                'required' => true,
            )
        );

        $rangeEnd = new \Tillikum_Form_Element_Date(
            'range_end',
            array(
                'label' => 'What date is the latest a booking included in this report can end?',
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $rangeStart,
                $rangeEnd,
            )
        );
    }
}

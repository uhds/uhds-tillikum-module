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

class CheckinRoster extends ReportForm
{
    public function init()
    {
        parent::init();

        $rangeStart = new \Tillikum_Form_Element_Date(
            'range_start',
            [
                'label' => 'What is the earliest date a booking included in this report can have?',
                'required' => true,
            ]
        );

        $rangeEnd = new \Tillikum_Form_Element_Date(
            'range_end',
            [
                'label' => 'What is the latest date a booking included in this report can have?',
                'required' => true,
            ]
        );

        $this->addElements([
            $rangeStart,
            $rangeEnd,
        ]);
    }
}

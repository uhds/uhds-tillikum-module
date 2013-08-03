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

class CancellationAudit extends ReportForm
{
    public function init()
    {
        parent::init();

        $rangeStart = new \Tillikum_Form_Element_Date(
            'range_start',
            array(
                'label' => 'What cancellation date is the earliest to include in this report?',
                'required' => true,
            )
        );

        $rangeEnd = new \Tillikum_Form_Element_Date(
            'range_end',
            array(
                'label' => 'What cancellation date is the latest to include in this report?',
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

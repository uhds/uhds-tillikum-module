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

class RoomChanges extends ReportForm
{
    public function init()
    {
        parent::init();

        $date = new \Tillikum_Form_Element_Date(
            'date',
            array(
                'label' => 'Select the date on which the room changes are considered in progress.',
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $date,
            )
        );
    }
}

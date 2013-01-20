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

class IntoNew extends ReportForm
{
    public function init()
    {
        parent::init();

        $reservationDate = new \Tillikum_Form_Element_Date(
            'reservation_date',
            array(
                'label' => 'Show new reservations as of which date?',
                'required' => true,
            )
        );

        $bookingDate = new \Tillikum_Form_Element_Date(
            'booking_date',
            array(
                'label' => 'Show bookings as of which date?',
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $reservationDate,
                $bookingDate,
            )
        );
    }
}

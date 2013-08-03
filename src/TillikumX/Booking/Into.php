<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Booking;

use Tillikum\Booking\AbstractPendingBooking;

class Into extends AbstractPendingBooking
{
    /**
     * @return string
     */
    public function getActionHelperName()
    {
        return 'DataTablePendingInto';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'All bookings pending for INTO-OSU residents.';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'INTO-OSU';
    }

    /**
     * @return string
     */
    public function getViewHelperName()
    {
        return 'DataTablePendingInto';
    }
}

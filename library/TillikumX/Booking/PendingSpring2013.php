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

class PendingSpring2013 extends AbstractPendingBooking
{
    /**
     * @return string
     */
    public function getActionHelperName()
    {
        return 'DataTablePendingSpring2013';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'All bookings pending for the spring 2013 housing application.';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Spring 2013';
    }

    /**
     * @return string
     */
    public function getViewHelperName()
    {
        return 'DataTablePendingSpring2013';
    }
}

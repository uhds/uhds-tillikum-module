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

class PendingFall2013Return extends AbstractPendingBooking
{
    /**
     * @return string
     */
    public function getActionHelperName()
    {
        return 'DataTablePendingFall2013Return';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'All bookings pending for the fall 2013 returning student housing application.';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Fall 2013 returning students';
    }

    /**
     * @return string
     */
    public function getViewHelperName()
    {
        return 'DataTablePendingFall2013Return';
    }
}

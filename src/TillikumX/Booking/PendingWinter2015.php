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

class PendingWinter2015 extends AbstractPendingBooking
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getActionHelperName()
    {
        return 'DataTablePendingWinter2015';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDescription()
    {
        return 'All bookings pending for the winter 2015 housing application.';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Winter 2015';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getViewHelperName()
    {
        return 'DataTablePendingWinter2015';
    }
}

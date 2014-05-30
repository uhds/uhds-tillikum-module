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

class PendingSummer2014 extends AbstractPendingBooking
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getActionHelperName()
    {
        return 'DataTablePendingSummer2014';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDescription()
    {
        return 'All bookings pending for the summer 2014 housing application.';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Summer 2014';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getViewHelperName()
    {
        return 'DataTablePendingSummerHousingApplication';
    }
}

<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\View\Helper;

class DataTablePendingSummer2013 extends DataTablePendingSummerHousingApplication
{
    public function dataTablePendingSummer2013($rows)
    {
        return parent::dataTablePendingSummerHousingApplication($rows);
    }

    public function getCaption()
    {
        return 'Pending summer 2013 housing applications';
    }
}

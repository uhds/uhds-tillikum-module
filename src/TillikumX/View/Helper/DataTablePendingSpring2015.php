<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\View\Helper;

class DataTablePendingSpring2015 extends DataTablePendingHousingApplication
{
    public function dataTablePendingSpring2015($rows)
    {
        return parent::dataTablePendingHousingApplication($rows);
    }

    public function getCaption()
    {
        return 'Pending spring 2015 housing applications';
    }
}
